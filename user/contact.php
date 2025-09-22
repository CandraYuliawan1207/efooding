<?php
require_once '../components/functions.php';
requireLogin();

// Set title untuk header
$page_title = "Kontak - E-Fooding System";

// Include header
include '../components/header.php';

// Proses form contact jika disubmit
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_contact'])) {
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $subjek = trim($_POST['subjek']);
    $pesan = trim($_POST['pesan']);
    $kategori = $_POST['kategori'];
    
    // Validasi
    if (empty($nama) || empty($email) || empty($subjek) || empty($pesan)) {
        $error = 'Semua field wajib diisi!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid!';
    } else {
        // Simpan ke database (atau kirim email)
        require_once '../components/connect.php';
        $database = new Database();
        $db = $database->getConnection();
        
        try {
            $query = "INSERT INTO contact_messages (user_id, nama, email, kategori, subjek, pesan, status) 
                     VALUES (:user_id, :nama, :email, :kategori, :subjek, :pesan, 'unread')";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $_SESSION['user_id']);
            $stmt->bindParam(':nama', $nama);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':kategori', $kategori);
            $stmt->bindParam(':subjek', $subjek);
            $stmt->bindParam(':pesan', $pesan);
            
            if ($stmt->execute()) {
                $success = 'Pesan Anda berhasil dikirim! Kami akan membalas dalam 1x24 jam.';
            } else {
                $error = 'Gagal mengirim pesan. Silakan coba lagi.';
            }
        } catch (PDOException $e) {
            $error = 'Terjadi kesalahan sistem: ' . $e->getMessage();
        }
    }
}
?>

<style>
    .contact-container {
        min-height: calc(100vh - 160px);
        display: flex;
        align-items: center;
        padding: 2rem 0;
    }
    
    .contact-card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        overflow: hidden;
        margin-bottom: 1.5rem;
    }
    
    .contact-info {
        background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
        color: white;
        border-radius: 15px;
        padding: 2rem;
        height: 100%;
    }
    
    .contact-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: rgba(255,255,255,0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
    }
    
    .info-item {
        text-align: center;
        margin-bottom: 2rem;
    }
    
    .info-item:last-child {
        margin-bottom: 0;
    }
    
    .info-item h5 {
        font-weight: 600;
        margin-bottom: 0.5rem;
    }
    
    .info-item p {
        margin-bottom: 0;
        opacity: 0.9;
    }
    
    .card-header {
        background: linear-gradient(to right, #f8f9fa, #e9ecef);
        padding: 1.5rem;
        border-bottom: 1px solid rgba(0,0,0,0.05);
    }
    
    .form-label {
        font-weight: 500;
        margin-bottom: 0.5rem;
        color: #495057;
    }
    
    .form-control, .form-select {
        border-radius: 8px;
        padding: 0.75rem 1rem;
        border: 1px solid #ced4da;
        transition: all 0.3s;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #3498db;
        box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25);
    }
    
    .btn-primary {
        background: linear-gradient(to right, #3498db, #2c3e50);
        border: none;
        padding: 0.75rem 2rem;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(52, 152, 219, 0.4);
    }
    
    .page-title {
        position: relative;
        margin-bottom: 3rem;
    }
    
    .page-title:after {
        content: '';
        display: block;
        width: 80px;
        height: 4px;
        background: linear-gradient(to right, #3498db, #2c3e50);
        margin: 0.5rem auto 0;
        border-radius: 2px;
    }
    
    @media (max-width: 992px) {
        .contact-info {
            margin-top: 2rem;
        }
    }
</style>

<div class="contact-container">
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="text-center mb-5">
                    <h1 class="display-5 fw-bold text-primary page-title">Hubungi Kami</h1>
                    <p class="lead text-muted">Butuh bantuan? Silakan hubungi admin via pesan langsung</p>
                </div>

                <!-- Notifikasi -->
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show mb-4">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show mb-4">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Form Contact -->
                    <div class="col-lg-8">
                        <div class="card contact-card">
                            <div class="card-header">
                                <h4 class="mb-0"><i class="fas fa-envelope me-2 text-primary"></i>Kirim Pesan</h4>
                            </div>
                            <div class="card-body p-4">
                                <form method="POST">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Nama Lengkap *</label>
                                                <input type="text" class="form-control" name="nama" 
                                                       value="<?php echo $_SESSION['username']; ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Email *</label>
                                                <input type="email" class="form-control" name="email" 
                                                       value="<?php echo $_SESSION['email'] ?? ''; ?>" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Kategori *</label>
                                        <select class="form-select" name="kategori" required>
                                            <option value="">Pilih Kategori</option>
                                            <option value="technical">Technical Support</option>
                                            <option value="fooding">Pengajuan Fooding</option>
                                            <option value="complaint">Keluhan</option>
                                            <option value="suggestion">Saran</option>
                                            <option value="other">Lainnya</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Subjek *</label>
                                        <input type="text" class="form-control" name="subjek" 
                                               placeholder="Contoh: Problem pengajuan fooding" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Pesan *</label>
                                        <textarea class="form-control" name="pesan" rows="5" 
                                                  placeholder="Tulis pesan Anda di sini..." required></textarea>
                                    </div>

                                    <button type="submit" name="submit_contact" class="btn btn-primary btn-lg w-100 mt-3">
                                        <i class="fas fa-paper-plane me-2"></i>Kirim Pesan
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Info -->
                    <div class="col-lg-4">
                        <div class="contact-info">
                            <div class="info-item">
                                <div class="contact-icon">
                                    <i class="fas fa-phone fa-lg"></i>
                                </div>
                                <h5>Telepon</h5>
                                <p>+62 822-8207-6291</p>
                            </div>

                            <div class="info-item">
                                <div class="contact-icon">
                                    <i class="fas fa-envelope fa-lg"></i>
                                </div>
                                <h5>Email</h5>
                                <p>candrayln275@gmail.com</p>
                            </div>

                            <div class="info-item">
                                <div class="contact-icon">
                                    <i class="fas fa-clock fa-lg"></i>
                                </div>
                                <h5>Jam Operasional</h5>
                                <p>Senin - Sabtu<br>08:00 - 15:00 WIB</p>
                            </div>

                            <div class="info-item">
                                <div class="contact-icon">
                                    <i class="fas fa-map-marker-alt fa-lg"></i>
                                </div>
                                <h5>Lokasi</h5>
                                <p>PT. Selatan Agro Makmur Lestari<br>Sumatera Selatan</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include '../components/footer.php';
?>