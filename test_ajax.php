<?php
// test_ajax.php - Test AJAX notifikasi
session_start();
$_SESSION['user_id'] = 1; // Set manual untuk testing

require_once 'components/connect.php';
$database = new Database();
$db = $database->getConnection();

// Test insert notifikasi
$message = "Test notifikasi " . date('H:i:s');
$query = "INSERT INTO notifications (user_id, message) VALUES (1, ?)";
$stmt = $db->prepare($query);

if ($stmt->execute([$message])) {
    echo "Notifikasi test berhasil ditambahkan: " . $message;
} else {
    echo "Gagal menambah notifikasi test";
}
?>