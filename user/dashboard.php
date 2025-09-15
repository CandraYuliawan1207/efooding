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

// PROSES: Tandai semua notifikasi sebagai sudah dibaca
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_all_read'])) {
    try {
        $updateQuery = "UPDATE notifications SET status = 'read' 
                      WHERE user_id = :user_id AND status = 'unread'";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->bindParam(':user_id', $user_id);

        if ($updateStmt->execute()) {
            setNotification('Semua notifikasi ditandai sudah dibaca', 'success');
            header("Location: dashboard.php");
            exit();
        }
    } catch (Exception $e) {
        setNotification('Gagal menandai notifikasi: ' . $e->getMessage(), 'error');
    }
}

// Ambil notifikasi
$notifQuery = "SELECT * FROM notifications 
               WHERE user_id = :user_id 
               ORDER BY timestamp DESC 
               LIMIT 10";
$notifStmt = $db->prepare($notifQuery);
$notifStmt->bindParam(':user_id', $user_id);
$notifStmt->execute();
$notifications = $notifStmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung notifikasi unread untuk badge
$unreadCount = 0;
foreach ($notifications as $notif) {
    if ($notif['status'] == 'unread') {
        $unreadCount++;
    }
}
?>

<?php include '../components/header.php'; ?>

<div class="row">
    <div class="col-md-12">
        <div class="welcome-card card bg-gradient-primary text-white mb-4 fade-in">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2 class="card-title">Selamat Datang, <?php echo $_SESSION['username']; ?>!</h2>
                        <p class="card-text">Anda login dari departemen <?php echo $_SESSION['department']; ?>. Silakan ajukan fooding atau lihat riwayat pengajuan Anda.</p>
                    </div>
                    <div class="col-md-4 text-center">
                        <img src="../assets/images/welcome.png" alt="Welcome Icon"
                            class="img-fluid opacity-80" style="max-width: 100px;">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row fade-in">
    <div class="col-md-12">
        <h3 class="mb-4">Statistik Pengajuan</h3>
    </div>
</div>

<div class="row fade-in">
    <!-- Statistik Card -->
    <div class="col-md-4 mb-4">
        <div class="card stat-card bg-primary text-white smooth-hover">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h5 class="card-title">Total Pengajuan</h5>
                        <h2 class="card-stat"><?php echo $total; ?></h2>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="fas fa-clipboard-list fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-4">
        <div class="card stat-card bg-warning text-dark smooth-hover">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h5 class="card-title">Menunggu</h5>
                        <h2 class="card-stat"><?php echo $menunggu; ?></h2>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="fas fa-clock fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-4">
        <div class="card stat-card bg-success text-white smooth-hover">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h5 class="card-title">Disetujui</h5>
                        <h2 class="card-stat"><?php echo $disetujui; ?></h2>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row fade-in">
    <div class="col-md-8">
        <div class="card smooth-hover">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0"><i class="fas fa-history me-2"></i>Pengajuan Terakhir</h5>
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
                    <div class="text-center py-4">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Belum ada pengajuan fooding.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card smooth-hover">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0"><i class="fas fa-bolt me-2"></i>Aksi Cepat</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="ajukan.php" class="btn btn-primary mb-3 py-3">
                        <i class="fas fa-plus me-2"></i>Ajukan Fooding
                    </a>
                    <a href="riwayat.php" class="btn btn-outline-primary mb-3 py-3">
                        <i class="fas fa-history me-2"></i>Lihat Riwayat
                    </a>
                </div>
            </div>
        </div>

        <!-- Notifikasi -->
        <div class="card smooth-hover mt-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="fas fa-bell me-2"></i>Notifikasi Terbaru</h5>
                <?php if ($unreadCount > 0): ?>
                    <span class="badge bg-danger"><?php echo $unreadCount; ?> baru</span>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <?php if (count($notifications) > 0): ?>

                    <!-- Tombol Tandai Sudah Dibaca Semua -->
                    <?php if ($unreadCount > 0): ?>
                        <div class="p-2 bg-light border-bottom text-center">
                            <form method="POST">
                                <button type="submit" name="mark_all_read" class="btn btn-sm btn-success">
                                    <i class="fas fa-check-circle me-1"></i>Tandai Sudah Dibaca Semua
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>

                    <!-- Daftar Notifikasi (MAKSIMAL 3) -->
                    <?php
                    $displayNotifications = array_slice($notifications, 0, 3);
                    foreach ($displayNotifications as $notif): ?>
                        <div class="notification-item p-3 border-bottom">
                            <div class="d-flex align-items-start">
                                <div class="flex-grow-1">
                                    <?php if ($notif['status'] == 'unread'): ?>
                                        <span class="badge bg-danger float-end">Baru</span>
                                    <?php endif; ?>
                                    <p class="mb-1"><?php echo htmlspecialchars($notif['message']); ?></p>
                                    <small class="text-muted">
                                        <?php echo date('d M Y H:i', strtotime($notif['timestamp'])); ?>
                                    </small>
                                </div>
                                <div class="flex-shrink-0 ms-2">
                                    <?php
                                    $icon = 'fa-bell';
                                    $color = 'text-warning';
                                    if (strpos($notif['message'], 'disetujui') !== false) {
                                        $icon = 'fa-check-circle';
                                        $color = 'text-success';
                                    } elseif (strpos($notif['message'], 'ditolak') !== false) {
                                        $icon = 'fa-times-circle';
                                        $color = 'text-danger';
                                    } elseif (strpos($notif['message'], 'diperiksa') !== false) {
                                        $icon = 'fa-search';
                                        $color = 'text-info';
                                    }
                                    ?>
                                    <i class="fas <?php echo $icon; ?> <?php echo $color; ?>"></i>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <!-- Tombol Lihat Semua Notifikasi -->
                    <?php if (count($notifications) > 3): ?>
                        <div class="p-3 text-center bg-light">
                            <a href="notifikasi.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-list me-1"></i>Lihat Semua Notifikasi
                                <span class="badge bg-white text-dark ms-1"><?php echo count($notifications); ?></span>
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="p-2 text-center bg-light">
                            <a href="notifikasi.php" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-history me-1"></i>Lihat Riwayat Notifikasi
                            </a>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="p-4 text-center text-muted">
                        <i class="fas fa-bell-slash fa-2x mb-3"></i>
                        <p class="mb-0">Belum ada notifikasi</p>
                        <small>Notifikasi akan muncul di sini</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../components/footer.php'; ?>