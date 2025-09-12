<?php
// ajukan_simple.php - SUPER SIMPLE VERSION YANG PASTI BERHASIL
session_start();

// 1. CEK LOGIN - SANGAT SIMPLE
if (!isset($_SESSION['user_id'])) {
    die('<div class="alert alert-danger">Silakan login dulu. <a href="login.php">Login</a></div>');
}

// 2. KONEKSI DATABASE SEDERHANA
try {
    $db = new PDO('mysql:host=localhost;dbname=efooding_db', 'root', 'admin');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('<div class="alert alert-danger">Database error: ' . $e->getMessage() . '</div>');
}

// 3. PROSES PENGAJUAN - SANGAT SEDERHANA
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajukan_fooding'])) {
    $jumlah = (int)$_POST['jumlah'];
    $user_id = $_SESSION['user_id'];

    try {
        // BEGIN TRANSACTION
        $db->beginTransaction();

        // 3.1. SIMPAN PENGAJUAN
        $stmt = $db->prepare("INSERT INTO fooding_requests (user_id, jumlah, status) VALUES (?, ?, 'Menunggu')");
        $stmt->execute([$user_id, $jumlah]);

        // 3.2. KURANGI STOK
        $stmt = $db->prepare("UPDATE stock SET quantity = quantity - ? WHERE item_name = 'Indomie'");
        $stmt->execute([$jumlah]);

        $stmt = $db->prepare("UPDATE stock SET quantity = quantity - ? WHERE item_name = 'Kopi'");
        $stmt->execute([$jumlah]);

        // COMMIT TRANSACTION
        $db->commit();

        $success = "Pengajuan berhasil dikirim!";
    } catch (Exception $e) {
        $db->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}

// 4. AMBIL DATA STOK
$stok_indomie = 0;
$stok_kopi = 0;

try {
    $stmt = $db->prepare("SELECT * FROM stock WHERE item_name IN ('Indomie', 'Kopi')");
    $stmt->execute();
    $stok_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($stok_items as $item) {
        if ($item['item_name'] == 'Indomie') $stok_indomie = $item['quantity'];
        if ($item['item_name'] == 'Kopi') $stok_kopi = $item['quantity'];
    }
} catch (Exception $e) {
    $error .= " | Stok error: " . $e->getMessage();
}

$max_paket = min($stok_indomie, $stok_kopi);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajukan Fooding - Simple Version</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>

<body>
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Ajukan Fooding - Simple Version</h4>
                    </div>

                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
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
                    <a href="dashboard.php" class="btn btn-secondary">Kembali ke Dashboard</a>
                    <a href="ajukan.php" class="btn btn-outline-primary">Kembali ke Versi Lengkap</a>
                </div>
            </div>
        </div>
    </div>

    <!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html> -->