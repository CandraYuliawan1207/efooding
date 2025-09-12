<?php
// DI VERY TOP - sebelum apapun
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Include yang diperlukan
require_once '../components/functions.php';
require_once '../components/connect.php';

// Redirect jika belum login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Handle success/error messages from redirect
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $_SESSION['notification'] = [
        'message' => 'Pengajuan fooding berhasil dikirim!',
        'type' => 'success'
    ];
}

$database = new Database();
$db = $database->getConnection();

// PROSES PENGAJUAN
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajukan_fooding'])) {
    $jumlah = (int)$_POST['jumlah'];
    $user_id = $_SESSION['user_id'];

    try {
        // BEGIN TRANSACTION
        $db->beginTransaction();

        // 1. Simpan pengajuan
        $query = "INSERT INTO fooding_requests (user_id, jumlah, status) VALUES (?, ?, 'Menunggu')";
        $stmt = $db->prepare($query);
        $stmt->execute([$user_id, $jumlah]);

        // 2. Kurangi stok
        $stmt = $db->prepare("UPDATE stock SET quantity = quantity - ? WHERE item_name = 'Indomie'");
        $stmt->execute([$jumlah]);

        $stmt = $db->prepare("UPDATE stock SET quantity = quantity - ? WHERE item_name = 'Kopi'");
        $stmt->execute([$jumlah]);

        // COMMIT TRANSACTION
        $db->commit();

        // REDIRECT SEBELUM OUTPUT APAPUN
        header("Location: ajukan.php?success=1");
        exit();
        
    } catch (Exception $e) {
        // ROLLBACK jika error
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $_SESSION['error'] = "Error: " . $e->getMessage();
        header("Location: ajukan.php?error=1");
        exit();
    }
}

// Ambil data stok
$stok_indomie = 100;
$stok_kopi = 100;

try {
    $stmt = $db->prepare("SELECT * FROM stock WHERE item_name IN ('Indomie', 'Kopi')");
    $stmt->execute();
    $stok = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($stok as $item) {
        if ($item['item_name'] == 'Indomie') $stok_indomie = $item['quantity'];
        if ($item['item_name'] == 'Kopi') $stok_kopi = $item['quantity'];
    }
} catch (Exception $e) {
    // Gunakan nilai default jika error
}

$max_paket = min($stok_indomie, $stok_kopi);
?>

<?php include '../components/header.php'; ?>


<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Ajukan Fooding</h4>
                </div>

                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <!-- Info Stok -->
                    <div class="alert alert-info">
                        <strong>Stok Tersedia:</strong><br>
                        Indomie: <?php echo $stok_indomie; ?> pcs<br>
                        Kopi: <?php echo $stok_kopi; ?> pcs<br>
                        <strong>Maksimal: <?php echo $max_paket; ?> paket</strong>
                    </div>

                    <!-- Form Sederhana -->
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Jumlah Paket</label>
                            <input type="number" class="form-control" name="jumlah"
                                value="1" min="1" max="<?php echo $max_paket; ?>" required>
                        </div>

                        <button type="submit" name="ajukan_fooding" class="btn btn-primary w-100">
                            Ajukan Fooding
                        </button>
                    </form>
                </div>
            </div>

            <div class="text-center mt-3">
                <a href="dashboard.php" class="btn btn-secondary">Kembali</a>
            </div>
        </div>
    </div>
</div>

<?php include '../components/footer.php'; ?>