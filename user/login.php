<?php
require_once '../components/functions.php';

// Redirect jika sudah login
if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

// Proses login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitizeInput($_POST['username']);
    $password = sanitizeInput($_POST['password']);
    
    // Validasi input
    if (empty($username) || empty($password)) {
        setNotification('Username dan password harus diisi', 'error');
    } else {
        // Koneksi database
        require_once '../components/connect.php';
        $database = new Database();
        $db = $database->getConnection();
        
        // Query untuk mencari user
        $query = "SELECT * FROM users WHERE username = :username AND password = :password";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $password);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Login berhasil
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['department'] = $user['department'];
            $_SESSION['role'] = $user['role'];
            
            // Redirect ke dashboard
            setNotification('Login berhasil!', 'success');
            header("Location: dashboard.php");
            exit();
        } else {
            setNotification('Username atau password salah', 'error');
        }
    }
}
?>

<?php include '../components/header.php'; ?>
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <img src="../assets/images/logo.svg" alt="Logo" height="200" style="margin-top: -6em; margin-bottom:-5em;">
                    <h3 class="mt-3">Login User</h3>
                    <p class="text-muted">Masuk ke sistem E-Fooding</p>
                </div>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">Login</button>
                    </div>
                </form>
                
                <div class="text-center mt-3">
                    <p>Login sebagai admin? <a href="../admin/login.php">Klik di sini</a></p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../components/footer.php'; ?>