<?php
require_once '../components/functions.php';
requireAdminLogin();

// Koneksi database
require_once '../components/connect.php';
$database = new Database();
$db = $database->getConnection();

// Ambil semua pengajuan
$query = "SELECT fr.*, u.username, u.department 
          FROM fooding_requests fr 
          JOIN users u ON fr.user_id = u.id 
          ORDER BY fr.tanggal DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$pengajuan = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Proses update status
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $request_id = $_POST['request_id'];
    $new_status = $_POST['status'];

    // Mulai transaction
    $db->beginTransaction();

    try {
        // 1. Update status pengajuan
        $query = "UPDATE fooding_requests SET status = :status WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':status', $new_status);
        $stmt->bindParam(':id', $request_id);
        $stmt->execute();

        // 2. Dapatkan user_id dari pengajuan
        $query = "SELECT user_id, jumlah FROM fooding_requests WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $request_id);
        $stmt->execute();
        $pengajuan = $stmt->fetch(PDO::FETCH_ASSOC);
        $user_id = $pengajuan['user_id'];
        $jumlah = $pengajuan['jumlah'];

        // 3. Buat notifikasi untuk user
        $message = "Status pengajuan fooding " . $jumlah . " paket diubah menjadi: " . $new_status;
        $query = "INSERT INTO notifications (user_id, message) VALUES (:user_id, :message)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':message', $message);
        $stmt->execute();

        // Commit transaction
        $db->commit();

        setNotification('Status pengajuan berhasil diperbarui', 'success');
        header("Location: manage.php");
        exit();
    } catch (Exception $e) {
        // Rollback transaction jika ada error
        $db->rollBack();
        setNotification('Gagal memperbarui status: ' . $e->getMessage(), 'error');
    }
}
?>

<?php include '../components/header.php'; ?>
<div class="row">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Kelola Pengajuan Fooding</h2>
            <div>
                <button class="btn btn-outline-secondary" id="filterToggle">
                    <i class="fas fa-filter me-2"></i>Filter
                </button>
            </div>
        </div>

        <!-- Filter Form -->
        <div class="card mb-4 d-none" id="filterForm">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="">Semua Status</option>
                            <option value="Menunggu">Menunggu</option>
                            <option value="Diperiksa">Diperiksa</option>
                            <option value="Disetujui">Disetujui</option>
                            <option value="Ditolak">Ditolak</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tanggal Dari</label>
                        <input type="date" class="form-control" name="dari_tanggal">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tanggal Sampai</label>
                        <input type="date" class="form-control" name="sampai_tanggal">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div>
                            <button type="submit" class="btn btn-primary">Terapkan Filter</button>
                            <a href="manage.php" class="btn btn-secondary">Reset</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card smooth-hover">
            <div class="card-body">
                <?php if (count($pengajuan) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>User</th>
                                    <th>Departemen</th>
                                    <th>Jumlah</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pengajuan as $p): ?>
                                    <tr>
                                        <td><?php echo date('d M Y H:i', strtotime($p['tanggal'])); ?></td>
                                        <td><?php echo $p['username']; ?></td>
                                        <td><?php echo $p['department']; ?></td>
                                        <td><?php echo $p['jumlah']; ?> paket</td>
                                        <td>
                                            <?php
                                            $badge_class = '';
                                            switch ($p['status']) {
                                                case 'Menunggu':
                                                    $badge_class = 'bg-warning';
                                                    break;
                                                case 'Diperiksa':
                                                    $badge_class = 'bg-info';
                                                    break;
                                                case 'Disetujui':
                                                    $badge_class = 'bg-success';
                                                    break;
                                                case 'Ditolak':
                                                    $badge_class = 'bg-danger';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge <?php echo $badge_class; ?>"><?php echo $p['status']; ?></span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $p['id']; ?>">
                                                <i class="fas fa-edit"></i> Ubah Status
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- Modal Edit Status -->
                                    <div class="modal fade" id="editModal<?php echo $p['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Ubah Status Pengajuan</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="request_id" value="<?php echo $p['id']; ?>">
                                                        <div class="mb-3">
                                                            <label class="form-label">Status Saat Ini</label>
                                                            <input type="text" class="form-control" value="<?php echo $p['status']; ?>" readonly>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Status Baru</label>
                                                            <select class="form-select" name="status" required>
                                                                <option value="Menunggu" <?php echo $p['status'] == 'Menunggu' ? 'selected' : ''; ?>>Menunggu</option>
                                                                <option value="Diperiksa" <?php echo $p['status'] == 'Diperiksa' ? 'selected' : ''; ?>>Diperiksa</option>
                                                                <option value="Disetujui" <?php echo $p['status'] == 'Disetujui' ? 'selected' : ''; ?>>Disetujui</option>
                                                                <option value="Ditolak" <?php echo $p['status'] == 'Ditolak' ? 'selected' : ''; ?>>Ditolak</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" name="update_status" class="btn btn-primary">Simpan Perubahan</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                        <p class="text-muted">Belum ada pengajuan fooding.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    // Toggle filter form
    document.getElementById('filterToggle').addEventListener('click', function() {
        document.getElementById('filterForm').classList.toggle('d-none');
    });
</script>

<?php include '../components/footer.php'; ?>