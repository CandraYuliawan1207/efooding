<?php
// Include functions
require_once 'functions.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Fooding - PT. Selatan Agro Makmur Lestari</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <!-- Loader -->
    <div class="loader-wrapper" id="loader">
        <div class="loader">
            <div class="loader-logo">
                <i class="fas fa-utensils"></i>
                <span>E-Fooding</span>
            </div>
            <div class="loader-spinner"></div>
        </div>
    </div>

    <!-- Header Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <div class="brand-logo">
                    <i class="fas fa-utensils me-2"></i>
                    <span>E-Fooding</span>
                </div>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="../user/dashboard.php">
                                <i class="fas fa-home me-1"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../user/ajukan.php">
                                <i class="fas fa-plus-circle me-1"></i>Ajukan Fooding
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../user/riwayat.php">
                                <i class="fas fa-history me-1"></i>Riwayat
                            </a>
                        </li>
                    <?php elseif (isAdminLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="../admin/dashboard.php">
                                <i class="fas fa-home me-1"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../admin/manage.php">
                                <i class="fas fa-tasks me-1"></i>Kelola Pengajuan
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../admin/stock.php">
                                <i class="fas fa-boxes me-1"></i>Kelola Stok
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../admin/laporan.php">
                                <i class="fas fa-chart-bar me-1"></i>Laporan
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle user-menu" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <div class="user-avatar" style="text-align: center;">
                                    <i class="fas fa-user-circle"></i>
                                </div>
                                <div class="user-info">
                                    <span class="user-name"><?php echo $_SESSION['username']; ?></span>
                                    <small class="user-role"><?php echo $_SESSION['department']; ?></small>
                                </div>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="../user/dashboard.php">
                                        <i class="fas fa-user me-2"></i>Profil
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="../user/logout.php">
                                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php elseif (isAdminLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle user-menu" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
                                <div class="user-avatar admin">
                                    <i class="fas fa-user-cog"></i>
                                </div>
                                <div class="user-info">
                                    <span class="user-name"><?php echo $_SESSION['admin_username']; ?></span>
                                    <small class="user-role">Administrator</small>
                                </div>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="../admin/dashboard.php">
                                        <i class="fas fa-cog me-2"></i>Pengaturan
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="../admin/logout.php">
                                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="../user/login.php">
                                <i class="fas fa-user me-1"></i>Login User
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../admin/login.php">
                                <i class="fas fa-user-cog me-1"></i>Login Admin
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container-fluid main-content">
        <div class="container mt-5 pt-4">
            <?php displayNotification(); ?>