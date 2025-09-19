<?php
// user/profil.php dan admin/profil.php
require_once '../components/functions.php';
requireLogin();

// Koneksi database
require_once '../components/connect.php';
$database = new Database();
$db = $database->getConnection();

// Ambil data user yang login
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Proses ubah password
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ubah_password'])) {
    $password_baru = $_POST['password_baru'];
    $konfirmasi_password = $_POST['konfirmasi_password'];
    
    // Validasi
    if (empty($password_baru) || empty($konfirmasi_password)) {
        $error = 'Semua field harus diisi';
    } elseif ($password_baru !== $konfirmasi_password) {
        $error = 'Password baru dan konfirmasi tidak cocok';
    } elseif (strlen($password_baru) < 6) {
        $error = 'Password minimal 6 karakter';
    } else {
        // Update password (tanpa hashing)
        $query = "UPDATE users SET password = :password WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':password', $password_baru);
        $stmt->bindParam(':id', $user_id);
        
        if ($stmt->execute()) {
            $success = 'Password berhasil diubah';
            // Refresh data user
            $query = "SELECT * FROM users WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $user_id);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $error = 'Gagal mengubah password';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - E-Fooding</title>
    
    <!-- Bootstrap & Font Awesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .password-field {
            position: relative;
        }
        .toggle-password {
            position: absolute;
            right: 12px;
            top: 1.8em;
            bottom: 0;
            margin: auto;
            height: fit-content;
            cursor: pointer;
            background: none;
            border: none;
            color: #6c757d;
            display: flex;
            align-items: center;
            padding: 0;
        }
        .card-profile {
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .current-password {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <?php 
    // Sesuaikan path header berdasarkan role
    if ($_SESSION['role'] === 'admin') {
        include '../admin/components/header.php';
    } else {
        include '../components/header.php';
    }
    ?>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card card-profile">
                    <div class="card-header bg-primary text-white">
                        <h4 class="card-title mb-0">
                            <i class="fas fa-user-circle me-2"></i>Profil Pengguna
                        </h4>
                    </div>
                    <div class="card-body">
                        <!-- Notifikasi -->
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success alert-dismissible fade show">
                                <?php echo $success; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Informasi Profil -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label fw-bold">Username</label>
                                    <p class="form-control-plaintext"><?php echo htmlspecialchars($user['username']); ?></p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label fw-bold">Departemen</label>
                                    <p class="form-control-plaintext"><?php echo htmlspecialchars($user['department']); ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label fw-bold">Role</label>
                                    <p class="form-control-plaintext">
                                        <?php 
                                        $role_labels = [
                                            'user' => 'User',
                                            'admin' => 'Administrator',
                                            'kasie' => 'Kepala Seksi'
                                        ];
                                        echo $role_labels[$user['role']] ?? $user['role'];
                                        ?>
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label fw-bold">Tanggal Bergabung</label>
                                    <p class="form-control-plaintext">
                                        <?php echo date('d F Y', strtotime($user['created_at'])); ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Form Ubah Password -->
                        <div class="border-top pt-4">
                            <h5 class="mb-4">
                                <i class="fas fa-lock me-2"></i>Ubah Password
                            </h5>
                            
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label class="form-label">Password Saat Ini</label>
                                            <input type="text" class="form-control current-password text-muted" value="<?php echo htmlspecialchars($user['password']); ?>" readonly>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3 password-field">
                                            <label class="form-label">Password Baru</label>
                                            <input type="password" class="form-control" name="password_baru" required minlength="6">
                                            <button type="button" class="toggle-password" onclick="togglePassword(this)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3 password-field">
                                            <label class="form-label">Konfirmasi Password Baru</label>
                                            <input type="password" class="form-control" name="konfirmasi_password" required minlength="6">
                                            <button type="button" class="toggle-password" onclick="togglePassword(this)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" name="ubah_password" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Simpan Password Baru
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Fungsi toggle password visibility
    function togglePassword(button) {
        const input = button.previousElementSibling;
        const icon = button.querySelector('i');
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    // Auto close alerts
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
    </script>

<?php include '../components/footer.php'; ?>
</body>
</html>