<?php
require_once '../components/functions.php';
requireAdminLogin();

// Set title untuk header
$page_title = "Tentang Sistem - E-Fooding Admin";

// Include header
include '../components/header.php';
?>

<style>
    .hero-section {
        background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
        color: white;
        padding: 80px 0;
        margin-top: -20px;
    }
    
    .feature-card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        transition: transform 0.3s ease;
        height: 100%;
    }
    
    .feature-card:hover {
        transform: translateY(-5px);
    }

   .tech-icon {
    color: #3498db;
   }
    
    .stat-number {
        font-size: 3rem;
        font-weight: bold;
        color: #3498db;
    }
    
    .tech-icon {
        font-size: 3rem;
        margin-bottom: 1rem;
    }
    
    .back-button {
        position: fixed;
        bottom: 20px;
        left: 20px;
        z-index: 1000;
    }
    
    .admin-feature {
        border-left: 4px solid #3498db;
        padding-left: 1rem;
    }
</style>

<!-- Back Button -->
<a href="dashboard.php" class="btn btn-primary back-button rounded-circle shadow" 
   data-bs-toggle="tooltip" data-bs-placement="right" title="Kembali ke Dashboard">
    <i class="fas fa-arrow-left"></i>
</a>

<!-- Hero Section -->
<section class="hero-section rounded-5">
    <div class="container text-center">
        <h1 class="display-4 fw-bold mb-3">Admin Panel E-Fooding</h1>
        <p class="lead mb-4">Sistem Manajemen Extra Fooding Modern untuk Administrator</p>
        <div class="d-flex justify-content-center gap-3 flex-wrap">
            <a href="#features" class="btn btn-light btn-lg">Fitur Admin</a>
            <a href="#technology" class="btn btn-outline-light btn-lg">Teknologi</a>
        </div>
    </div>
</section>

<!-- Admin Features -->
<section id="features" class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-4 fw-bold">Fitur Administrator</h2>
            <p class="lead text-muted">Tools lengkap untuk manajemen sistem pengajuan extra fooding</p>
        </div>
        
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="admin-feature">
                    <h4 class="fw-bold text-primary">Manajemen Pengajuan</h4>
                    <p>Kelola semua pengajuan fooding dari berbagai departemen</p>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="admin-feature">
                    <h4 class="fw-bold text-primary">Approval System</h4>
                    <p>Setujui atau tolak pengajuan dengan workflow yang jelas</p>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="admin-feature">
                    <h4 class="fw-bold text-primary">Manajemen Stok</h4>
                    <p>Pantau dan kelola stok makanan secara real-time</p>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="admin-feature">
                    <h4 class="fw-bold text-primary">Laporan & Analytics</h4>
                    <p>Generate laporan lengkap untuk analisis data</p>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="admin-feature">
                    <h4 class="fw-bold text-primary">User Management</h4>
                    <p>Kelola akses dan permissions pengguna sistem</p>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="admin-feature">
                    <h4 class="fw-bold text-primary">Notification System</h4>
                    <p>Kirim notifikasi otomatis ke user dan kasie</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Technology Stack -->
<section id="technology" class="py-5 bg-light rounded-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-4 fw-bold">Teknologi Sistem</h2>
            <p class="lead text-muted">Platform modern untuk performa terbaik</p>
        </div>
        
        <div class="row text-center">
            <div class="col-md-3 mb-4">
                <i class="fab fa-php tech-icon"></i>
                <h5>PHP 8.1+</h5>
                <p class="text-muted">Backend Framework</p>
            </div>
            <div class="col-md-3 mb-4">
                <i class="fab fa-bootstrap tech-icon"></i>
                <h5>Bootstrap 5</h5>
                <p class="text-muted">Frontend Framework</p>
            </div>
            <div class="col-md-3 mb-4">
                <i class="fas fa-database tech-icon"></i>
                <h5>MySQL 8.0</h5>
                <p class="text-muted">Database System</p>
            </div>
            <div class="col-md-3 mb-4">
                <i class="fab fa-js-square tech-icon"></i>
                <h5>JavaScript</h5>
                <p class="text-muted">Client-side Scripting</p>
            </div>
        </div>
    </div>
</section>

<!-- System Statistics -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-4 fw-bold">Statistik Sistem</h2>
            <p class="lead text-muted">Data terkini penggunaan sistem</p>
        </div>
        
        <div class="row text-center">
            <?php
            // Koneksi database
            require_once '../components/connect.php';
            $database = new Database();
            $db = $database->getConnection();
            
            // 1. Total Pengajuan Bulan Ini
            $query = "SELECT COUNT(*) as total FROM fooding_requests 
                     WHERE MONTH(tanggal) = MONTH(CURRENT_DATE()) 
                     AND YEAR(tanggal) = YEAR(CURRENT_DATE())";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $total_pengajuan = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // 2. Approval Rate
            $query = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN status IN ('Disetujui', 'Disetujui Sebagian') THEN 1 ELSE 0 END) as approved
                     FROM fooding_requests 
                     WHERE status != 'Menunggu'";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $approval_data = $stmt->fetch(PDO::FETCH_ASSOC);
            $approval_rate = $approval_data['total'] > 0 ? 
                round(($approval_data['approved'] / $approval_data['total']) * 100, 1) : 0;
            
            // 3. Active Users (user yang pernah login dalam 30 hari terakhir)
            $query = "SELECT COUNT(DISTINCT user_id) as active_users 
                     FROM fooding_requests 
                     WHERE tanggal >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $active_users = $stmt->fetch(PDO::FETCH_ASSOC)['active_users'];
            
            // 4. Uptime (simulasi - dalam real project bisa dari monitoring system)
            // Untuk demo, kita hitung berdasarkan successful requests
            $query = "SELECT 
                        COUNT(*) as total_requests,
                        SUM(CASE WHEN status != 'Error' THEN 1 ELSE 0 END) as successful_requests
                     FROM fooding_requests 
                     WHERE tanggal >= DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY)";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $uptime_data = $stmt->fetch(PDO::FETCH_ASSOC);
            $uptime = $uptime_data['total_requests'] > 0 ? 
                round(($uptime_data['successful_requests'] / $uptime_data['total_requests']) * 100, 1) : 100;
            ?>
            
            <!-- Total Pengajuan Bulan Ini -->
            <div class="col-md-3 mb-4">
                <div class="stat-number" id="stat-pengajuan"><?php echo number_format($total_pengajuan); ?></div>
                <p class="fw-bold">Total Pengajuan</p>
                <small class="text-muted">Bulan <?php echo date('F Y'); ?></small>
            </div>
            
            <!-- Approval Rate -->
            <div class="col-md-3 mb-4">
                <div class="stat-number" id="stat-approval"><?php echo $approval_rate; ?>%</div>
                <p class="fw-bold">Approval Rate</p>
                <small class="text-muted">Tingkat Persetujuan</small>
            </div>
            
            <!-- Active Users -->
            <div class="col-md-3 mb-4">
                <div class="stat-number" id="stat-users"><?php echo number_format($active_users); ?></div>
                <p class="fw-bold">Active Users</p>
                <small class="text-muted">30 Hari Terakhir</small>
            </div>
            
            <!-- Uptime -->
            <div class="col-md-3 mb-4">
                <div class="stat-number" id="stat-uptime"><?php echo $uptime; ?>%</div>
                <p class="fw-bold">Uptime</p>
                <small class="text-muted">7 Hari Terakhir</small>
            </div>
        </div>
        
        <!-- Refresh Button (Optional) -->
        <div class="text-center mt-4">
            <button class="btn btn-outline-primary btn-sm" onclick="refreshStats()">
                <i class="fas fa-sync-alt me-1"></i>Refresh Data
            </button>
        </div>
    </div>
</section>

<script>
$(document).ready(function() {
    // Smooth scroll untuk anchor links
    $('a[href^="#"]').on('click', function(e) {
        e.preventDefault();
        var target = this.hash;
        var $target = $(target);
        
        $('html, body').animate({
            'scrollTop': $target.offset().top - 70
        }, 800, 'swing', function() {
            window.location.hash = target;
        });
    });

    // Initialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
});


// Function untuk refresh stats (optional)
function refreshStats() {
    const refreshBtn = event.target;
    const originalHtml = refreshBtn.innerHTML;
    
    // Tampilkan loading
    refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Loading...';
    refreshBtn.disabled = true;
    
    // Reload halaman setelah 1 detik
    setTimeout(() => {
        window.location.reload();
    }, 1000);
}

// Animation untuk stats counter (jika ingin animasi)
document.addEventListener('DOMContentLoaded', function() {
    animateValue('stat-pengajuan', 0, <?php echo $total_pengajuan; ?>, 1000);
    animateValue('stat-approval', 0, <?php echo $approval_rate; ?>, 1000);
    animateValue('stat-users', 0, <?php echo $active_users; ?>, 1000);
    animateValue('stat-uptime', 0, <?php echo $uptime; ?>, 1000);
});

function animateValue(id, start, end, duration) {
    const element = document.getElementById(id);
    if (!element) return;
    
    const range = end - start;
    const increment = end > start ? 1 : -1;
    const stepTime = Math.abs(Math.floor(duration / range));
    let current = start;
    
    const timer = setInterval(function() {
        current += increment;
        
        if (id === 'stat-approval' || id === 'stat-uptime') {
            element.textContent = current + '%';
        } else {
            element.textContent = current.toLocaleString();
        }
        
        if (current === end) {
            clearInterval(timer);
        }
    }, stepTime);
}

</script>

<?php
// Include footer
include '../components/footer.php';
?>