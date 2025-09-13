<?php
require_once '../components/functions.php';
requireAdminLogin();

// Koneksi database
require_once '../components/connect.php';
$database = new Database();
$db = $database->getConnection();

// Inisialisasi variabel filter
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_dari = isset($_GET['dari_tanggal']) ? $_GET['dari_tanggal'] : '';
$filter_sampai = isset($_GET['sampai_tanggal']) ? $_GET['sampai_tanggal'] : '';

// Build query dengan filter
$query = "SELECT fr.*, u.username, u.department 
          FROM fooding_requests fr 
          JOIN users u ON fr.user_id = u.id 
          WHERE 1=1";

$params = [];

// Filter status
if (!empty($filter_status)) {
    $query .= " AND fr.status = :status";
    $params[':status'] = $filter_status;
}

// Filter tanggal dari
if (!empty($filter_dari)) {
    $query .= " AND DATE(fr.tanggal) >= :dari_tanggal";
    $params[':dari_tanggal'] = $filter_dari;
}

// Filter tanggal sampai
if (!empty($filter_sampai)) {
    $query .= " AND DATE(fr.tanggal) <= :sampai_tanggal";
    $params[':sampai_tanggal'] = $filter_sampai;
}

$query .= " ORDER BY fr.tanggal DESC";

// Prepare dan execute query dengan filter
$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
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
        $pengajuan_data = $stmt->fetch(PDO::FETCH_ASSOC);
        $user_id = $pengajuan_data['user_id'];
        $jumlah = $pengajuan_data['jumlah'];

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

        // Redirect dengan menjaga parameter filter
        $filter_params = [];
        if (!empty($filter_status)) $filter_params['status'] = $filter_status;
        if (!empty($filter_dari)) $filter_params['dari_tanggal'] = $filter_dari;
        if (!empty($filter_sampai)) $filter_params['sampai_tanggal'] = $filter_sampai;

        $query_string = !empty($filter_params) ? '?' . http_build_query($filter_params) : '';
        header("Location: manage.php" . $query_string);
        exit();
    } catch (Exception $e) {
        // Rollback transaction jika ada error
        $db->rollBack();
        setNotification('Gagal memperbarui status: ' . $e->getMessage(), 'error');
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengajuan - E-Fooding</title>

    <!-- Bootstrap & Font Awesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* FIX MODAL ISSUES */
        .modal-backdrop {
            z-index: 1040 !important;
        }

        .modal {
            z-index: 1050 !important;
            background-color: rgba(0, 0, 0, 0.5) !important;
        }

        .modal-content {
            z-index: 1060 !important;
            position: relative;
        }

        body.modal-open {
            overflow: auto;
            padding-right: 0 !important;
        }

        /* Custom Styles */
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            border-radius: 10px 10px 0 0 !important;
        }

        .btn {
            border-radius: 6px;
        }

        .table th {
            border-top: none;
            font-weight: 600;
            color: #495057;
        }
    </style>
</head>

<body>
    <?php include '../components/header.php'; ?>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="mb-1">Kelola Pengajuan Fooding</h2>
                        <p class="text-muted mb-0">Manajemen pengajuan extra fooding dari user</p>
                    </div>
                    <div>
                        <button class="btn btn-outline-primary" id="filterToggle">
                            <i class="fas fa-filter me-2"></i>Filter
                            <?php if (!empty($filter_status) || !empty($filter_dari) || !empty($filter_sampai)): ?>
                                <span class="badge bg-danger ms-1">Aktif</span>
                            <?php endif; ?>
                        </button>
                    </div>
                </div>

                <!-- Filter Form -->
                <div class="card mb-4 <?php echo (empty($filter_status) && empty($filter_dari) && empty($filter_sampai)) ? 'd-none' : ''; ?>" id="filterForm">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Filter Pengajuan</h6>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status">
                                    <option value="">Semua Status</option>
                                    <option value="Menunggu" <?php echo $filter_status == 'Menunggu' ? 'selected' : ''; ?>>Menunggu</option>
                                    <option value="Diperiksa" <?php echo $filter_status == 'Diperiksa' ? 'selected' : ''; ?>>Diperiksa</option>
                                    <option value="Disetujui" <?php echo $filter_status == 'Disetujui' ? 'selected' : ''; ?>>Disetujui</option>
                                    <option value="Ditolak" <?php echo $filter_status == 'Ditolak' ? 'selected' : ''; ?>>Ditolak</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Tanggal Dari</label>
                                <input type="date" class="form-control" name="dari_tanggal"
                                    value="<?php echo $filter_dari; ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Tanggal Sampai</label>
                                <input type="date" class="form-control" name="sampai_tanggal"
                                    value="<?php echo $filter_sampai; ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-check me-1"></i>Terapkan
                                    </button>
                                    <a href="manage.php" class="btn btn-secondary">
                                        <i class="fas fa-times me-1"></i>Reset
                                    </a>
                                </div>
                            </div>
                        </form>

                        <!-- Info Filter Aktif -->
                        <?php if (!empty($filter_status) || !empty($filter_dari) || !empty($filter_sampai)): ?>
                            <div class="mt-3 p-2 bg-light rounded">
                                <small class="text-muted">
                                    <strong>Filter Aktif:</strong>
                                    <?php
                                    $active_filters = [];
                                    if (!empty($filter_status)) $active_filters[] = "Status: $filter_status";
                                    if (!empty($filter_dari)) $active_filters[] = "Dari: " . date('d M Y', strtotime($filter_dari));
                                    if (!empty($filter_sampai)) $active_filters[] = "Sampai: " . date('d M Y', strtotime($filter_sampai));
                                    echo implode(', ', $active_filters);
                                    ?>
                                </small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Statistik Cepat -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white text-center">
                            <div class="card-body py-3">
                                <h6 class="card-title">Total Pengajuan</h6>
                                <h4 class="mb-0"><?php echo count($pengajuan); ?></h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-dark text-center">
                            <div class="card-body py-3">
                                <h6 class="card-title">Menunggu</h6>
                                <h4 class="mb-0">
                                    <?php echo count(array_filter($pengajuan, function ($p) {
                                        return $p['status'] == 'Menunggu';
                                    })); ?>
                                </h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white text-center">
                            <div class="card-body py-3">
                                <h6 class="card-title">Diperiksa</h6>
                                <h4 class="mb-0">
                                    <?php echo count(array_filter($pengajuan, function ($p) {
                                        return $p['status'] == 'Diperiksa';
                                    })); ?>
                                </h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white text-center">
                            <div class="card-body py-3">
                                <h6 class="card-title">Disetujui</h6>
                                <h4 class="mb-0">
                                    <?php echo count(array_filter($pengajuan, function ($p) {
                                        return $p['status'] == 'Disetujui';
                                    })); ?>
                                </h4>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabel Pengajuan -->
                <div class="card">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Daftar Pengajuan</h5>
                            <small class="text-muted">
                                Menampilkan <?php echo count($pengajuan); ?> data
                                <?php if (!empty($filter_status) || !empty($filter_dari) || !empty($filter_sampai)): ?>
                                    (difilter)
                                <?php endif; ?>
                            </small>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (count($pengajuan) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
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
                                                <td>
                                                    <div><?php echo date('d M Y', strtotime($p['tanggal'])); ?></div>
                                                    <small class="text-muted"><?php echo date('H:i', strtotime($p['tanggal'])); ?></small>
                                                </td>
                                                <td><?php echo htmlspecialchars($p['username']); ?></td>
                                                <td><?php echo htmlspecialchars($p['department']); ?></td>
                                                <td>
                                                    <span class="badge bg-secondary"><?php echo $p['jumlah']; ?> paket</span>
                                                </td>
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
                                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                                        onclick="openModal('editModal<?php echo $p['id']; ?>')">
                                                        <i class="fas fa-edit me-1"></i>Ubah Status
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                                <p class="text-muted">
                                    <?php if (!empty($filter_status) || !empty($filter_dari) || !empty($filter_sampai)): ?>
                                        Tidak ada pengajuan yang sesuai dengan filter yang dipilih.
                                    <?php else: ?>
                                        Belum ada pengajuan fooding.
                                    <?php endif; ?>
                                </p>
                                <?php if (!empty($filter_status) || !empty($filter_dari) || !empty($filter_sampai)): ?>
                                    <a href="manage.php" class="btn btn-primary mt-2">
                                        <i class="fas fa-times me-2"></i>Hapus Filter
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODALS - HARUS DI LUAR CONTAINER -->
    <?php foreach ($pengajuan as $p): ?>
        <div class="modal fade" id="editModal<?php echo $p['id']; ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Ubah Status Pengajuan</h5>
                        <button type="button" class="btn-close" onclick="closeModal('editModal<?php echo $p['id']; ?>')"></button>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="request_id" value="<?php echo $p['id']; ?>">
                        <div class="modal-body">
                            <div class="row mb-3">
                                <div class="col-6">
                                    <label class="form-label">User</label>
                                    <input type="text" class="form-control"
                                        value="<?php echo htmlspecialchars($p['username']); ?>" readonly>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Jumlah</label>
                                    <input type="text" class="form-control"
                                        value="<?php echo $p['jumlah']; ?> paket" readonly>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Status Saat Ini</label>
                                <input type="text" class="form-control"
                                    value="<?php echo $p['status']; ?>" readonly>
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
                            <button type="button" class="btn btn-secondary" onclick="closeModal('editModal<?php echo $p['id']; ?>')">Batal</button>
                            <button type="submit" name="update_status" class="btn btn-primary">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <?php include '../components/footer.php'; ?>

    <script>
        // Toggle filter form
        document.getElementById('filterToggle').addEventListener('click', function() {
            document.getElementById('filterForm').classList.toggle('d-none');
        });

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
                // Gunakan Bootstrap modal jika available
                if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    const bsModal = new bootstrap.Modal(modal);
                    bsModal.show();
                } else {
                    // Fallback manual
                    modal.style.display = 'block';
                    modal.classList.add('show');
                    document.body.classList.add('modal-open');

                    // Create backdrop
                    const backdrop = document.createElement('div');
                    backdrop.className = 'modal-backdrop fade show';
                    backdrop.style.zIndex = '1040';
                    document.body.appendChild(backdrop);
                }
            }
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                // Gunakan Bootstrap modal jika available
                if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    const bsModal = bootstrap.Modal.getInstance(modal);
                    if (bsModal) {
                        bsModal.hide();
                    }
                } else {
                    // Fallback manual
                    modal.style.display = 'none';
                    modal.classList.remove('show');
                    document.body.classList.remove('modal-open');

                    // Remove backdrop
                    const backdrop = document.querySelector('.modal-backdrop');
                    if (backdrop) {
                        backdrop.remove();
                    }
                }
            }
        }

        // Bootstrap modal initialization
        document.addEventListener('DOMContentLoaded', function() {
            // Handle modal backdrop issues
            document.addEventListener('show.bs.modal', function() {
                const backdrops = document.querySelectorAll('.modal-backdrop');
                backdrops.forEach(backdrop => {
                    backdrop.style.zIndex = '1040';
                });
            });
        });

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

</body>

</html>