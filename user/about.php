<?php
require_once '../components/functions.php';
requireLogin();

// Set title untuk header
$page_title = "Tentang Kami - E-Fooding System";

// Include header
include '../components/header.php';
?>

<style>
    .hero-section {
        background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
        color: white;
        padding: 80px 0;
        margin-top: -20px;
        border-radius:2em;
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

    .hover-img {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .hover-img:hover {
        transform: translateY(-10px); /* naik sedikit (melayang) */
        box-shadow: 12px 12px 25px rgba(0, 0, 0, 0.6); /* bayangan makin tebal */
    }
    
    .timeline {
        position: relative;
        padding-left: 3rem;
    }
    
    .timeline::before {
        content: '';
        position: absolute;
        left: 15px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #3498db;
    }
    
    .timeline-item {
        position: relative;
        margin-bottom: 2rem;
    }
    
    .timeline-item::before {
        content: '';
        position: absolute;
        left: -3rem;
        top: 0.5rem;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: #3498db;
        border: 4px solid white;
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
</style>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container text-center">
        <h1 class="display-4 fw-bold mb-3">Tentang E-Fooding System</h1>
        <p class="lead mb-4">Sistem Manajemen Pengajuan Extra Fooding Modern untuk Karyawan</p>
        <div class="d-flex justify-content-center gap-3 flex-wrap">
            <a href="#features" class="btn btn-light btn-lg">Fitur Sistem</a>
            <a href="#how-it-works" class="btn btn-outline-light btn-lg">Cara Kerja</a>
        </div>
    </div>
</section>

<!-- About Company -->
<section class="py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h2 class="display-5 fw-bold text-primary mb-4">PT. Selatan Agro Makmur Lestari</h2>
                <p class="lead mb-4">
                    Perusahaan perkebunan dan pabrik kelapa sawit dalam naungan SPO Group yang berkomitmen untuk memberikan yang terbaik 
                    bagi karyawan melalui sistem pengajuan extra fooding yang modern dan efisien.
                </p>
                <div class="row">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center mb-3">
                            <div class="stat-number">300+</div>
                            <div class="ms-3">
                                <small class="text-muted">Karyawan</small>
                                <div class="fw-bold">Active Talent</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-center mb-3">
                            <div class="stat-number">1K+</div>
                            <div class="ms-3">
                                <small class="text-muted">Paket</small>
                                <div class="fw-bold">Per Month</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 text-center">
  <a href="https://cyuliawanportofolio.site/" target="_blank" rel="noopener noreferrer" class="hover-img-link">
    <img src="../assets/images/developer.png" 
         alt="Company Overview" 
         class="img-fluid rounded-3 hover-img"
         style="max-height: 33em; object-fit: contain; box-shadow: 8px 8px 15px rgba(0,0,0,0.50);">
  </a>
</div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section id="features" class="py-5 bg-light rounded-4">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-4 fw-bold">Fitur untuk Karyawan</h2>
            <p class="lead text-muted">Manfaatkan fitur-fitur modern untuk pengajuan fooding</p>
        </div>
        
        <div class="row g-4">
            <div class="col-md-4">
                <div class="feature-card p-4 text-center">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-mobile-alt fa-3x" style="color: #3498db;"></i>
                    </div>
                    <h4 class="fw-bold">Akses Mobile</h4>
                    <p class="text-muted">Ajukan fooding melalui smartphone kapan saja</p>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="feature-card p-4 text-center">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-bell fa-3x" style="color: #3498db;"></i>
                    </div>
                    <h4 class="fw-bold">Notifikasi Real-time</h4>
                    <p class="text-muted">Dapatkan notifikasi status pengajuan langsung</p>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="feature-card p-4 text-center">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-history fa-3x" style="color: #3498db;"></i>
                    </div>
                    <h4 class="fw-bold">Riwayat Pengajuan</h4>
                    <p class="text-muted">Lihat history pengajuan fooding Anda</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- How It Works -->
<section id="how-it-works" class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-4 fw-bold">Cara Kerja untuk User</h2>
            <p class="lead text-muted">Langkah-langkah pengajuan extra fooding</p> <hr style="text-primary">
        </div>
        
        <div class="timeline">
            <div class="timeline-item">
                <h4 class="fw-bold">1. Login ke Sistem</h4>
                <p>Masuk ke akun Anda menggunakan username dan password</p>
            </div>
            
            <div class="timeline-item">
                <h4 class="fw-bold">2. Ajukan Fooding</h4>
                <p>Isi form pengajuan dengan jumlah paket dan nama penerima</p>
            </div>
            
            <div class="timeline-item">
                <h4 class="fw-bold">3. Tunggu Approval</h4>
                <p>Pengajuan akan diproses dan diperiksa oleh Admin</p>
            </div>
            
            <div class="timeline-item">
                <h4 class="fw-bold">4. Terima Notifikasi</h4>
                <p>Dapatkan pemberitahuan status pengajuan</p>
            </div>
            
            <div class="timeline-item">
                <h4 class="fw-bold">5. Ambil Fooding</h4>
                <p>Ambil paket fooding yang sudah disetujui di waserda</p>
            </div>
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
</script>

<?php
// Include footer
include '../components/footer.php';
?>