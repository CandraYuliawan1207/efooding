<?php
// Koneksi ke database dengan PDO
$host = 'localhost';
$db = 'efooding_db';
$user = 'root';  // ganti dengan username DB
$pass = '';  // ganti dengan password DB

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    // Set error mode untuk menampilkan error
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}
?>
