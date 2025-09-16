<?php
date_default_timezone_set('Asia/Jakarta');
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Fungsi-fungsi lainnya...

// Redirect jika belum login
function requireLogin()
{
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../user/login.php");
        exit();
    }
}

// Redirect admin jika belum login
function requireAdminLogin()
{
    if (!isset($_SESSION['admin_id'])) {
        header("Location: ../admin/login.php");
        exit();
    }
}

// Cek apakah user sudah login
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

// Cek apakah admin sudah login
function isAdminLoggedIn()
{
    return isset($_SESSION['admin_id']);
}

// // Hash password
// function hashPassword($password) {
//     return password_hash($password, PASSWORD_DEFAULT);
// }

// // Verifikasi password
// function verifyPassword($password, $hashedPassword) {
//     return password_verify($password, $hashedPassword);
// }

// Set notifikasi
function setNotification($message, $type = 'info')
{
    $_SESSION['notification'] = [
        'message' => $message,
        'type' => $type
    ];
}

// Tampilkan notifikasi
function displayNotification()
{
    if (isset($_SESSION['notification'])) {
        $notification = $_SESSION['notification'];
        $alertClass = $notification['type'] == 'error' ? 'alert-danger' : ($notification['type'] == 'success' ? 'alert-success' : 'alert-info');

        echo '<div class="alert ' . $alertClass . ' alert-dismissible fade show" role="alert">
                ' . $notification['message'] . '
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>';

        unset($_SESSION['notification']);
    }
}

// Validasi input
function sanitizeInput($data)
{
    return htmlspecialchars(stripslashes(trim($data)));
}

// Format tanggal Indonesia
function formatDateIndonesian($date)
{
    $months = array(
        'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    );

    // Convert timezone jika perlu
    $timestamp = strtotime($date);
    return date('d', $timestamp) . ' ' . $months[date('n', $timestamp) - 1] . ' ' . date('Y', $timestamp);
}

// Fungsi baru untuk datetime lengkap
function formatDateTimeIndonesian($date)
{
    $months = array(
        'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    );

    $timestamp = strtotime($date);
    return date('d', $timestamp) . ' ' . $months[date('n', $timestamp) - 1] . ' ' .
        date('Y', $timestamp) . ' ' . date('H:i:s', $timestamp);
}
