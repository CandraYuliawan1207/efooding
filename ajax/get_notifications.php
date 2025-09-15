<?php
// ajax/get_notifications.php
session_start();
require_once '../components/connect.php';

if (!isset($_SESSION['user_id'])) {
    die('<div class="p-3 text-center text-danger">Silakan login kembali</div>');
}

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

try {
    // Ambil notifikasi terbaru
    $query = "SELECT * FROM notifications 
              WHERE user_id = :user_id 
              ORDER BY timestamp DESC 
              LIMIT 10";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Update status notifikasi menjadi read
    $updateQuery = "UPDATE notifications SET status = 'read' 
                    WHERE user_id = :user_id AND status = 'unread'";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->bindParam(':user_id', $user_id);
    $updateStmt->execute();

    if (count($notifications) > 0) {
        foreach ($notifications as $notif) {
            $time_ago = time_elapsed_string($notif['timestamp']);
            $icon = get_notification_icon($notif['message']);
            $is_new = $notif['status'] == 'unread' ? 'border-start border-3 border-primary' : '';

            echo '
            <div class="notification-item p-3 border-bottom ' . $is_new . '">
                <div class="d-flex align-items-start">
                    <div class="flex-shrink-0 me-3">
                        ' . $icon . '
                    </div>
                    <div class="flex-grow-1">
                        <p class="mb-1">' . htmlspecialchars($notif['message']) . '</p>
                        <small class="text-muted">' . $time_ago . '</small>
                    </div>
                </div>
            </div>';
        }

        echo '<div class="p-2 text-center bg-light">
                <a href="../user/riwayat.php" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-history me-1"></i>Lihat Riwayat
                </a>
              </div>';
    } else {
        echo '<div class="p-4 text-center text-muted">
                <i class="fas fa-bell-slash fa-2x mb-3"></i>
                <p class="mb-0">Belum ada notifikasi</p>
                <small>Notifikasi akan muncul di sini</small>
              </div>';
    }
} catch (Exception $e) {
    echo '<div class="p-3 text-center text-danger">
            <i class="fas fa-exclamation-circle me-2"></i>Error memuat notifikasi
          </div>';
}

// Fungsi untuk icon notifikasi
function get_notification_icon($message)
{
    if (strpos($message, 'disetujui') !== false) {
        return '<i class="fas fa-check-circle fa-lg text-success"></i>';
    } elseif (strpos($message, 'ditolak') !== false) {
        return '<i class="fas fa-times-circle fa-lg text-danger"></i>';
    } elseif (strpos($message, 'diperiksa') !== false) {
        return '<i class="fas fa-search fa-lg text-info"></i>';
    } elseif (strpos($message, 'diajukan') !== false) {
        return '<i class="fas fa-paper-plane fa-lg text-primary"></i>';
    } else {
        return '<i class="fas fa-bell fa-lg text-warning"></i>';
    }
}

// Fungsi format waktu
function time_elapsed_string($datetime)
{
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->d > 0) {
        return date('d M Y H:i', strtotime($datetime));
    } elseif ($diff->h > 0) {
        return $diff->h . ' jam yang lalu';
    } elseif ($diff->i > 0) {
        return $diff->i . ' menit yang lalu';
    } else {
        return 'Baru saja';
    }
}
