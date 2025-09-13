<?php
require_once '../components/functions.php';
requireAdminLogin();

// Koneksi database
require_once '../components/connect.php';
$database = new Database();
$db = $database->getConnection();

// Ambil data stok
$query = "SELECT * FROM stock ORDER BY item_name";
$stmt = $db->prepare($query);
$stmt->execute();
$stok = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Proses tambah stok
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_stok'])) {
    $item_name = $_POST['item_name'];
    $quantity = $_POST['quantity'];
    $unit = $_POST['unit'];

    $query = "INSERT INTO stock (item_name, quantity, unit) VALUES (:item_name, :quantity, :unit)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':item_name', $item_name);
    $stmt->bindParam(':quantity', $quantity);
    $stmt->bindParam(':unit', $unit);

    if ($stmt->execute()) {
        setNotification('Item stok berhasil ditambahkan', 'success');
        header("Location: stock.php");
        exit();
    } else {
        setNotification('Gagal menambahkan item stok', 'error');
    }
}

// Proses update stok
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_stok'])) {
    $item_id = $_POST['item_id'];
    $quantity = $_POST['quantity'];

    $query = "UPDATE stock SET quantity = :quantity WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':quantity', $quantity);
    $stmt->bindParam(':id', $item_id);

    if ($stmt->execute()) {
        setNotification('Stok berhasil diperbarui', 'success');
        header("Location: stock.php");
        exit();
    } else {
        setNotification('Gagal memperbarui stok', 'error');
    }
}

// Proses hapus stok
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['hapus_stok'])) {
    $item_id = $_POST['item_id'];

    $query = "DELETE FROM stock WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $item_id);

    if ($stmt->execute()) {
        setNotification('Item stok berhasil dihapus', 'success');
        header("Location: stock.php");
        exit();
    } else {
        setNotification('Gagal menghapus item stok', 'error');
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Stok - E-Fooding</title>

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
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="mb-1">Kelola Stok Fooding</h2>
                        <p class="text-muted mb-0">Manajemen stok extra fooding</p>
                    </div>
                </div>

                <!-- Form Tambah Stok -->
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0"><i class="fas fa-plus-circle me-2"></i>Tambah Item Stok Baru</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Nama Item</label>
                                <input type="text" class="form-control" name="item_name" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Jumlah</label>
                                <input type="number" class="form-control" name="quantity" min="0" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Satuan</label>
                                <input type="text" class="form-control" name="unit" value="pcs" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <div>
                                    <button type="submit" name="tambah_stok" class="btn btn-primary">Tambah</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Daftar Stok -->
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0"><i class="fas fa-boxes me-2"></i>Daftar Stok Tersedia</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($stok) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Nama Item</th>
                                            <th>Jumlah</th>
                                            <th>Satuan</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($stok as $item): ?>
                                            <tr>
                                                <td><?php echo $item['item_name']; ?></td>
                                                <td><?php echo $item['quantity']; ?></td>
                                                <td><?php echo $item['unit']; ?></td>
                                                <td>
                                                    <span class="badge <?php echo $item['quantity'] > 20 ? 'bg-success' : ($item['quantity'] > 5 ? 'bg-warning' : 'bg-danger'); ?>">
                                                        <?php echo $item['quantity'] > 20 ? 'Aman' : ($item['quantity'] > 5 ? 'Sedang' : 'Kritis'); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-outline-primary me-1"
                                                        onclick="openModal('editModal<?php echo $item['id']; ?>')">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                                        onclick="openModal('hapusModal<?php echo $item['id']; ?>')">
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
                                <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
                                <p class="text-muted">Belum ada data stok.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODALS - HARUS DI LUAR CONTAINER -->
    <?php foreach ($stok as $item): ?>
        <!-- Modal Edit Stok -->
        <div class="modal fade" id="editModal<?php echo $item['id']; ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Stok</h5>
                        <button type="button" class="btn-close" onclick="closeModal('editModal<?php echo $item['id']; ?>')"></button>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Nama Item</label>
                                <input type="text" class="form-control" value="<?php echo $item['item_name']; ?>" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Jumlah</label>
                                <input type="number" class="form-control" name="quantity" value="<?php echo $item['quantity']; ?>" min="0" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Satuan</label>
                                <input type="text" class="form-control" value="<?php echo $item['unit']; ?>" readonly>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="closeModal('editModal<?php echo $item['id']; ?>')">Batal</button>
                            <button type="submit" name="update_stok" class="btn btn-primary">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Hapus Stok -->
        <div class="modal fade" id="hapusModal<?php echo $item['id']; ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Hapus Item Stok</h5>
                        <button type="button" class="btn-close" onclick="closeModal('hapusModal<?php echo $item['id']; ?>')"></button>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                        <div class="modal-body">
                            <p>Apakah Anda yakin ingin menghapus item <strong><?php echo $item['item_name']; ?></strong>?</p>
                            <p class="text-danger"><small>Tindakan ini tidak dapat dibatalkan.</small></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="closeModal('hapusModal<?php echo $item['id']; ?>')">Batal</button>
                            <button type="submit" name="hapus_stok" class="btn btn-danger">Hapus</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
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
    <?php include '../components/footer.php'; ?>
</body>

</html>