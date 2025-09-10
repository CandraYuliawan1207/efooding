<?php
require_once '../components/functions.php';
require_once '../components/connect.php';

session_start();

// Untuk user, pastikan hanya mengambil notifikasi miliknya
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Ambil notifikasi terbaru
    $query = "SELECT * FROM notifications WHERE user_id = :user_id ORDER BY timestamp DESC LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($notifications) > 0) {
        echo '<div class="list-group list-group-flush">';
        foreach ($notifications as $notif) {
            $status_class = $notif['status'] == 'unread' ? 'list-group-item-primary' : '';
            echo '<div class="list-group-item ' . $status_class . '">';
            echo '<div class="d-flex align-items-center">';
            echo '<div class="flex-grow-1">';
            echo '<p class="mb-0">' . htmlspecialchars($notif['message']) . '</p>';
            echo '<small class="text-muted">' . date('d M Y H:i', strtotime($notif['timestamp'])) . '</small>';
            echo '</div>';
            if ($notif['status'] == 'unread') {
                echo '<span class="badge bg-primary rounded-pill">Baru</span>';
            }
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
        
        // Update status notifikasi menjadi read
        $query = "UPDATE notifications SET status = 'read' WHERE user_id = :user_id AND status = 'unread'";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
    } else {
        echo '<div class="p-3 text-center text-muted">';
        echo '<i class="fas fa-bell-slash fa-2x mb-2"></i>';
        echo '<p>Tidak ada notifikasi</p>';
        echo '</div>';
    }
} else {
    echo '<div class="p-3 text-center text-danger">';
    echo '<i class="fas fa-exclamation-triangle me-2"></i>Error: User tidak terautentikasi';
    echo '</div>';
}
?>