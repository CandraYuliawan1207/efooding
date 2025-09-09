<?php
session_start();
include('../components/connect.php');
include('../components/functions.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Ambil data pengajuan terakhir
$stmt = $pdo->prepare("SELECT * FROM fooding_requests WHERE user_id = :user_id ORDER BY tanggal DESC LIMIT 1");
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$last_request = $stmt->fetch(PDO::FETCH_ASSOC);

// Ambil riwayat pengajuan
$stmt = $pdo->prepare("SELECT * FROM fooding_requests WHERE user_id = :user_id ORDER BY tanggal DESC");
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<h1>Dashboard</h1>
<p>Status Pengajuan Terakhir: <?= $last_request['status'] ?></p>

<h2>Riwayat Pengajuan</h2>
<table>
    <tr>
        <th>Tanggal</th>
        <th>Status</th>
        <th>Jumlah</th>
    </tr>
    <?php foreach ($history as $row): ?>
        <tr>
            <td><?= $row['tanggal'] ?></td>
            <td><?= $row['status'] ?></td>
            <td><?= $row['jumlah'] ?></td>
        </tr>
    <?php endforeach; ?>
</table>
