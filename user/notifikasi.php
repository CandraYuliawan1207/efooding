<?php
require_once '../components/functions.php';
requireLogin();

// Koneksi database
require_once '../components/connect.php';
$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];

// PROSES: Tandai semua notifikasi sebagai sudah dibaca
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['mark_all_read'])) {
        try {
            $updateQuery = "UPDATE notifications SET status = 'read' 
                          WHERE user_id = :user_id AND status = 'unread'";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->bindParam(':user_id', $user_id);

            if ($updateStmt->execute()) {
                setNotification('Semua notifikasi ditandai sudah dibaca', 'success');
                header("Location: notifikasi.php");
                exit();
            }
        } catch (Exception $e) {
            setNotification('Gagal menandai notifikasi: ' . $e->getMessage(), 'error');
        }
    }

    // Tandai notifikasi tertentu sebagai sudah dibaca
    if (isset($_POST['mark_as_read'])) {
        $notif_id = $_POST['notif_id'];
        try {
            $updateQuery = "UPDATE notifications SET status = 'read' 
                          WHERE id = :id AND user_id = :user_id";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->bindParam(':id', $notif_id);
            $updateStmt->bindParam(':user_id', $user_id);

            if ($updateStmt->execute()) {
                setNotification('Notifikasi ditandai sudah dibaca', 'success');
                header("Location: notifikasi.php");
                exit();
            }
        } catch (Exception $e) {
            setNotification('Gagal menandai notifikasi: ' . $e->getMessage(), 'error');
        }
    }

    // HAPUS SEMUA NOTIFIKASI
    if (isset($_POST['delete_all'])) {
        try {
            $deleteQuery = "DELETE FROM notifications WHERE user_id = :user_id";
            $deleteStmt = $db->prepare($deleteQuery);
            $deleteStmt->bindParam(':user_id', $user_id);

            if ($deleteStmt->execute()) {
                setNotification('Semua notifikasi berhasil dihapus', 'success');
                header("Location: notifikasi.php");
                exit();
            }
        } catch (Exception $e) {
            setNotification('Gagal menghapus notifikasi: ' . $e->getMessage(), 'error');
        }
    }

    // HAPUS NOTIFIKASI TERTENTU
    if (isset($_POST['delete_notification'])) {
        $notif_id = $_POST['notif_id'];
        try {
            $deleteQuery = "DELETE FROM notifications WHERE id = :id AND user_id = :user_id";
            $deleteStmt = $db->prepare($deleteQuery);
            $deleteStmt->bindParam(':id', $notif_id);
            $deleteStmt->bindParam(':user_id', $user_id);

            if ($deleteStmt->execute()) {
                setNotification('Notifikasi berhasil dihapus', 'success');
                header("Location: notifikasi.php");
                exit();
            }
        } catch (Exception $e) {
            setNotification('Gagal menghapus notifikasi: ' . $e->getMessage(), 'error');
        }
    }
}

// Ambil semua notifikasi
$notifQuery = "SELECT * FROM notifications 
               WHERE user_id = :user_id 
               ORDER BY timestamp DESC";
$notifStmt = $db->prepare($notifQuery);
$notifStmt->bindParam(':user_id', $user_id);
$notifStmt->execute();
$all_notifications = $notifStmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung notifikasi unread
$unreadCount = 0;
foreach ($all_notifications as $notif) {
    if ($notif['status'] == 'unread') {
        $unreadCount++;
    }
}
?>

<?php include '../components/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1">Manajemen Notifikasi</h2>
                    <p class="text-muted mb-0">Riwayat semua notifikasi Anda</p>
                </div>
                <div>
                    <?php if (count($all_notifications) > 0): ?>
                        <div class="btn-group" role="group">
                            <?php if ($unreadCount > 0): ?>
                                <form method="POST" class="d-inline">
                                    <button type="submit" name="mark_all_read" class="btn btn-success me-2">
                                        <i class="fas fa-check-circle me-1"></i>Tandai Sudah Dibaca
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Statistik Notifikasi -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card bg-primary text-white text-center">
                        <div class="card-body py-3">
                            <h6 class="card-title">Total Notifikasi</h6>
                            <h4 class="mb-0"><?php echo count($all_notifications); ?></h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-warning text-dark text-center">
                        <div class="card-body py-3">
                            <h6 class="card-title">Belum Dibaca</h6>
                            <h4 class="mb-0"><?php echo $unreadCount; ?></h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-success text-white text-center">
                        <div class="card-body py-3">
                            <h6 class="card-title">Sudah Dibaca</h6>
                            <h4 class="mb-0"><?php echo count($all_notifications) - $unreadCount; ?></h4>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Daftar Semua Notifikasi -->
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-list me-2"></i>Semua Notifikasi
                        <span class="badge bg-primary"><?php echo count($all_notifications); ?></span>
                    </h5>

                    <?php if (count($all_notifications) > 0): ?>
                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteAllModal">
                            <i class="fas fa-trash me-1"></i>Hapus Semua
                        </button>
                    <?php endif; ?>
                </div>

                <div class="card-body p-0">
                    <?php if (count($all_notifications) > 0): ?>
                        <?php foreach ($all_notifications as $notif): ?>
                            <div class="notification-item p-3 border-bottom">
                                <div class="d-flex align-items-start">
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <?php if ($notif['status'] == 'unread'): ?>
                                                    <span class="badge bg-danger">Baru</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Sudah dibaca</span>
                                                <?php endif; ?>
                                            </div>

                                            <div class="btn-group">
                                                <?php if ($notif['status'] == 'unread'): ?>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="notif_id" value="<?php echo $notif['id']; ?>">
                                                        <button type="submit" name="mark_as_read" class="btn btn-sm btn-outline-success me-1">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>

                                                <!-- Tombol Hapus Notifikasi Tertentu -->
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="notif_id" value="<?php echo $notif['id']; ?>">
                                                    <button type="submit" name="delete_notification" class="btn btn-sm btn-outline-danger"
                                                        onclick="return confirm('Hapus notifikasi ini?')">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>

                                        <p class="mb-1"><?php echo htmlspecialchars($notif['message']); ?></p>
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            <?php echo date('d M Y H:i', strtotime($notif['timestamp'])); ?>
                                        </small>
                                    </div>
                                    <div class="flex-shrink-0 ms-3">
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
                                        <i class="fas <?php echo $icon; ?> fa-2x <?php echo $color; ?>"></i>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="p-5 text-center text-muted">
                            <i class="fas fa-bell-slash fa-4x mb-3"></i>
                            <h5>Belum ada notifikasi</h5>
                            <p>Semua notifikasi Anda akan muncul di halaman ini</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Hapus Semua Notifikasi -->
<div class="modal fade" id="deleteAllModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Hapus Semua Notifikasi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>PERHATIAN!</strong> Tindakan ini tidak dapat dibatalkan.
                </div>
                <p>Apakah Anda yakin ingin menghapus <strong>semua notifikasi</strong>?</p>
                <p class="text-muted">Total notifikasi yang akan dihapus: <?php echo count($all_notifications); ?></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <form method="POST">
                    <button type="submit" name="delete_all" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i>Ya, Hapus Semua
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../components/footer.php'; ?>