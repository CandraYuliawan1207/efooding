<?php
require_once '../components/functions.php';
requireAdminLogin();

// Koneksi database
require_once '../components/connect.php';
$database = new Database();
$db = $database->getConnection();

// Inisialisasi variabel
$error = '';
$success = '';

// PROSES TAMBAH USER
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_user'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $department = trim($_POST['department']);

    try {
        // Validasi input
        if (empty($username) || empty($password) || empty($department)) {
            throw new Exception("Semua field harus diisi!");
        }

        if (strlen($password) < 3) {
            throw new Exception("Password minimal 3 karakter!");
        }

        // Cek apakah username sudah ada
        $checkQuery = "SELECT id FROM users WHERE username = :username";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(':username', $username);
        $checkStmt->execute();

        if ($checkStmt->rowCount() > 0) {
            throw new Exception("Username sudah digunakan!");
        }

        // Insert user baru ke tabel users
        $query = "INSERT INTO users (username, password, department, role) 
                  VALUES (:username, :password, :department, 'user')";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $password);
        $stmt->bindParam(':department', $department);

        if ($stmt->execute()) {
            $success = "User berhasil ditambahkan!";
        } else {
            throw new Exception("Gagal menambahkan user!");
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// PROSES EDIT USER
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_user'])) {
    $user_id = $_POST['user_id'];
    $username = trim($_POST['username']);
    $department = trim($_POST['department']);
    $password = trim($_POST['password']);

    try {
        // Validasi input
        if (empty($username) || empty($department)) {
            throw new Exception("Username dan department harus diisi!");
        }

        // Cek apakah username sudah ada (kecuali untuk user ini)
        $checkQuery = "SELECT id FROM users WHERE username = :username AND id != :user_id";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(':username', $username);
        $checkStmt->bindParam(':user_id', $user_id);
        $checkStmt->execute();

        if ($checkStmt->rowCount() > 0) {
            throw new Exception("Username sudah digunakan!");
        }

        // Update user
        if (!empty($password)) {
            if (strlen($password) < 3) {
                throw new Exception("Password minimal 3 karakter!");
            }
            $query = "UPDATE users SET username = :username, password = :password, 
                      department = :department WHERE id = :id";
        } else {
            $query = "UPDATE users SET username = :username, department = :department WHERE id = :id";
        }

        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':department', $department);
        $stmt->bindParam(':id', $user_id);

        if (!empty($password)) {
            $stmt->bindParam(':password', $password);
        }

        if ($stmt->execute()) {
            $success = "User berhasil diperbarui!";
        } else {
            throw new Exception("Gagal memperbarui user!");
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// PROSES HAPUS USER
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['hapus_user'])) {
    $user_id = $_POST['user_id'];

    try {
        $query = "DELETE FROM users WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $user_id);

        if ($stmt->execute()) {
            $success = "User berhasil dihapus!";
        } else {
            throw new Exception("Gagal menghapus user!");
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// AMBIL DATA USER
$users = [];
try {
    $query = "SELECT * FROM users ORDER BY username";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "Gagal mengambil data user: " . $e->getMessage();
}
?>

<?php include '../components/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1">Kelola User</h2>
                    <p class="text-muted mb-0">Manajemen user sistem E-Fooding</p>
                </div>
            </div>

            <!-- Notifikasi -->
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Form Tambah User -->
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0"><i class="fas fa-user-plus me-2"></i>Tambah User Baru</h5>
                </div>
                <div class="card-body">
                    <form method="POST" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Department</label>
                            <input type="text" class="form-control" name="department" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" name="tambah_user" class="btn btn-primary w-100">Tambah</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Daftar User -->
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0"><i class="fas fa-users me-2"></i>Daftar User</h5>
                </div>
                <div class="card-body">
                    <?php if (count($users) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Username</th>
                                        <th>Department</th>
                                        <th>Role</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td><?php echo htmlspecialchars($user['department']); ?></td>
                                            <td>
                                                <span class="badge bg-primary">
                                                    <?php echo ucfirst($user['role']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-primary me-1"
                                                    onclick="openModal('editModal<?php echo $user['id']; ?>')">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <!-- Hapus pengecekan session user_id karena ini halaman admin -->
                                                <button type="button" class="btn btn-sm btn-outline-danger"
                                                    onclick="openModal('hapusModal<?php echo $user['id']; ?>')">
                                                    <i class="fas fa-trash"></i> Hapus
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-users fa-4x text-muted mb-3"></i>
                            <p class="text-muted">Belum ada data user.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODALS -->
<?php foreach ($users as $user): ?>
    <!-- Modal Edit User -->
    <div class="modal fade" id="editModal<?php echo $user['id']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit User</h5>
                    <button type="button" class="btn-close" onclick="closeModal('editModal<?php echo $user['id']; ?>')"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username"
                                value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password (Kosongkan jika tidak diubah)</label>
                            <input type="password" class="form-control" name="password"
                                placeholder="Masukkan password baru">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Department</label>
                            <input type="text" class="form-control" name="department"
                                value="<?php echo htmlspecialchars($user['department']); ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('editModal<?php echo $user['id']; ?>')">Batal</button>
                        <button type="submit" name="edit_user" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Hapus User -->
    <div class="modal fade" id="hapusModal<?php echo $user['id']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Hapus User</h5>
                    <button type="button" class="btn-close" onclick="closeModal('hapusModal<?php echo $user['id']; ?>')"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                    <div class="modal-body">
                        <p>Apakah Anda yakin ingin menghapus user <strong><?php echo htmlspecialchars($user['username']); ?></strong>?</p>
                        <p class="text-danger"><small>Tindakan ini tidak dapat dibatalkan.</small></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('hapusModal<?php echo $user['id']; ?>')">Batal</button>
                        <button type="submit" name="hapus_user" class="btn btn-danger">Hapus</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; ?>
<?php include '../components/footer.php'; ?>

<!-- JavaScript -->
<script>
    // Auto-close alerts setelah 5 detik
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);

    // Manual modal functions
    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
        }
    }

    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            const bsModal = bootstrap.Modal.getInstance(modal);
            if (bsModal) {
                bsModal.hide();
            }
        }
    }

    // Keyboard support untuk modal
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const openModals = document.querySelectorAll('.modal.show');
            openModals.forEach(modal => {
                closeModal(modal.id);
            });
        }
    });
</script>