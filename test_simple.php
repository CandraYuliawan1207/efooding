<?php
// test_simple.php
session_start();
$_SESSION['user_id'] = 1;

require_once 'components/connect.php';
$database = new Database();
$db = $database->getConnection();

// Simple insert
$query = "INSERT INTO fooding_requests (user_id, jumlah, status) VALUES (1, 1, 'Menunggu')";
$stmt = $db->prepare($query);
if ($stmt->execute()) {
    echo "SUCCESS - ID: " . $db->lastInsertId();
} else {
    echo "ERROR: " . implode(", ", $stmt->errorInfo());
}
?>