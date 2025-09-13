<?php
require_once '../components/functions.php';
requireLogin();

// Koneksi database
require_once '../components/connect.php';
$database = new Database();
$db = $database->getConnection();

// Ambil data stok untuk validasi
$query = "SELECT * FROM stock WHERE item_name IN ('Indomie', 'Kopi')";
$stmt = $db->prepare($query);
$stmt->execute();
$stok = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Siapkan data stok
$stok_indomie = 0;
$stok_kopi = 0;

foreach ($stok as $item) {
    if ($item['item_name'] == 'Indomie') {
        $stok_indomie = $item['quantity'];
    } elseif ($item['item_name'] == 'Kopi') {
        $stok_kopi = $item['quantity'];
    }
}

// Hitung maksimal paket yang bisa diajukan (terbatas oleh stok)
$max_paket = min(floor($stok_indomie / 1), floor($stok_kopi / 1));

// Proses pengajuan fooding - MODIFIED FOR REDIRECT BACK
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajukan_fooding'])) {
    $jumlah = (int)$_POST['jumlah'];

    // Validasi jumlah
    if ($jumlah <= 0) {
        setNotification('Jumlah paket harus lebih dari 0', 'error');
    } elseif ($jumlah > $max_paket) {
        setNotification('Stok tidak mencukupi. Maksimal ' . $max_paket . ' paket', 'error');
    } else {
        try {
            // Mulai transaction untuk atomic operation
            $db->beginTransaction();

            // Simpan pengajuan ke database
            $user_id = $_SESSION['user_id'];
            $status = 'Menunggu';

            $query = "INSERT INTO fooding_requests (user_id, jumlah, status) VALUES (:user_id, :jumlah, :status)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':jumlah', $jumlah);
            $stmt->bindParam(':status', $status);
            $stmt->execute();

            // Kurangi stok
            $query = "UPDATE stock SET quantity = quantity - :jumlah WHERE item_name = 'Indomie'";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':jumlah', $jumlah);
            $stmt->execute();

            $query = "UPDATE stock SET quantity = quantity - :jumlah WHERE item_name = 'Kopi'";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':jumlah', $jumlah);
            $stmt->execute();

            // Commit transaction
            $db->commit();

            // Set pesan sukses dan redirect ke ajukan.php
            setNotification('Pengajuan fooding berhasil dikirim!', 'success');
            header("Location: ajukan.php");
            exit();
        } catch (Exception $e) {
            // Rollback jika ada error
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            setNotification('Gagal mengajukan fooding: ' . $e->getMessage(), 'error');
            header("Location: ajukan.php");
            exit();
        }
    }
}
?>

<?php include '../components/header.php'; ?>
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card smooth-hover">
            <div class="card-header bg-white">
                <h2 class="card-title mb-0"><i class="fas fa-utensils me-2"></i>Ajukan Fooding</h2>
            </div>
            <div class="card-body">
                <!-- Info Stok -->
                <div class="alert alert-info">
                    <h5><i class="fas fa-info-circle me-2"></i>Informasi Stok</h5>
                    <p>1 paket fooding = 1 Indomie + 1 Kopi</p>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between">
                                <span>Stok Indomie:</span>
                                <strong><?php echo $stok_indomie; ?> pcs</strong>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between">
                                <span>Stok Kopi:</span>
                                <strong><?php echo $stok_kopi; ?> pcs</strong>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 p-3 bg-light rounded">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold">Maksimal paket yang dapat diajukan:</span>
                            <span class="badge bg-primary fs-6"><?php echo $max_paket; ?> paket</span>
                        </div>
                    </div>
                </div>

                <!-- Form Pengajuan -->
                <form method="POST" action="">
                    <div class="mb-4">
                        <label for="jumlah" class="form-label fs-5">Jumlah Paket Fooding</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text"><i class="fas fa-box-open"></i></span>
                            <input type="number" class="form-control" id="jumlah" name="jumlah"
                                min="1" max="<?php echo $max_paket; ?>"
                                required value="<?php echo isset($_POST['jumlah']) ? $_POST['jumlah'] : 1; ?>">
                            <span class="input-group-text">paket</span>
                        </div>
                        <div class="form-text">Masukkan jumlah paket yang ingin diajukan (maksimal <?php echo $max_paket; ?> paket)</div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" name="ajukan_fooding" class="btn btn-primary btn-lg py-3">
                            <i class="fas fa-paper-plane me-2"></i>Ajukan Fooding
                        </button>
                    </div>
                </form>

                <!-- Detail Paket -->
                <div class="mt-5">
                    <h4 class="mb-3">Detail Paket Fooding</h4>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <i class="fas fa-utensils fa-3x text-primary mb-3"></i>
                                    <h5>Indomie</h5>
                                    <p class="mb-0">1 bungkus per paket</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <i class="fas fa-coffee fa-3x text-secondary mb-3"></i>
                                    <h5>Kopi</h5>
                                    <p class="mb-0">1 sachet per paket</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Validasi real-time
    document.getElementById('jumlah').addEventListener('input', function() {
        const max = <?php echo $max_paket; ?>;
        const value = parseInt(this.value);

        if (value > max) {
            this.setCustomValidity('Maksimal ' + max + ' paket');
        } else {
            this.setCustomValidity('');
        }
    });
</script>

<?php include '../components/footer.php'; ?>