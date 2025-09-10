<?php
require_once '../components/functions.php';
requireLogin();

// Koneksi database
require_once '../components/connect.php';
$database = new Database();
$db = $database->getConnection();

// Ambil data statistik
$user_id = $_SESSION['user_id'];

// Total pengajuan
$query = "SELECT COUNT(*) as total FROM fooding_requests WHERE user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Pengajuan menunggu
$query = "SELECT COUNT(*) as menunggu FROM fooding_requests WHERE user_id = :user_id AND status = 'Menunggu'";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$menunggu = $stmt->fetch(PDO::FETCH_ASSOC)['menunggu'];

// Pengajuan disetujui
$query = "SELECT COUNT(*) as disetujui FROM fooding_requests WHERE user_id = :user_id AND status = 'Disetujui'";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$disetujui = $stmt->fetch(PDO::FETCH_ASSOC)['disetujui'];

// Pengajuan terakhir
$query = "SELECT * FROM fooding_requests WHERE user_id = :user_id ORDER BY tanggal DESC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$pengajuan_terakhir = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include '../components/header.php'; ?>
<div class="row">
    <div class="col-md-12">
        <h2 class="mb-4">Dashboard User</h2>
    </div>
</div>

<div class="row">
    <!-- Statistik Card -->
    <div class="col-md-4 mb-4">
        <div class="card stat-card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h5 class="card-title">Total Pengajuan</h5>
                        <h2 class="card-stat"><?php echo $total; ?></h2>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="fas fa-clipboard-list fa-3x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card stat-card bg-warning text-dark">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h5 class="card-title">Menunggu</h5>
                        <h2 class="card-stat"><?php echo $menunggu; ?></h2>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="fas fa-clock fa-3x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card stat-card bg-success text-white">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h5 class="card-title">Disetujui</h5>
                        <h2 class="card-stat"><?php echo $disetujui; ?></h2>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle fa-3x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Pengajuan Terakhir</h5>
            </div>
            <div class="card-body">
                <?php if (count($pengajuan_terakhir) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Jumlah</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pengajuan_terakhir as $pengajuan): ?>
                                    <tr>
                                        <td><?php echo formatDateIndonesian($pengajuan['tanggal']); ?></td>
                                        <td><?php echo $pengajuan['jumlah']; ?> paket</td>
                                        <td>
                                            <?php 
                                            $badge_class = '';
                                            switch ($pengajuan['status']) {
                                                case 'Menunggu':
                                                    $badge_class = 'bg-warning';
                                                    break;
                                                case 'Diperiksa':
                                                    $badge_class = 'bg-info';
                                                    break;
                                                case 'Disetujui':
                                                    $badge_class = 'bg-success';
                                                    break;
                                                case 'Ditolak':
                                                    $badge_class = 'bg-danger';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge <?php echo $badge_class; ?>"><?php echo $pengajuan['status']; ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">Belum ada pengajuan fooding.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Aksi Cepat</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="ajukan.php" class="btn btn-primary mb-2">
                        <i class="fas fa-plus me-2"></i>Ajukan Fooding
                    </a>
                    <a href="riwayat.php" class="btn btn-outline-primary mb-2">
                        <i class="fas fa-history me-2"></i>Lihat Riwayat
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Notifikasi -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title">Notifikasi Terbaru</h5>
            </div>
            <div class="card-body" id="notifications-container">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// AJAX untuk notifikasi
function loadNotifications() {
    $.ajax({
        url: '../ajax/get_notifications.php',
        type: 'GET',
        success: function(response) {
            $('#notifications-container').html(response);
        },
        error: function() {
            $('#notifications-container').html('<p class="text-muted">Gagal memuat notifikasi.</p>');
        }
    });
}

// Muat notifikasi pertama kali
loadNotifications();

// Polling notifikasi setiap 30 detik
setInterval(loadNotifications, 30000);
</script>

<?php include '../components/footer.php'; ?>