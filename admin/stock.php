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

<?php include '../components/header.php'; ?>
<div class="row">
    <div class="col-md-12">
        <h2 class="mb-4">Kelola Stok Fooding</h2>
        
        <!-- Form Tambah Stok -->
        <div class="card smooth-hover mb-4">
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
        <div class="card smooth-hover">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0"><i class="fas fa-boxes me-2"></i>Daftar Stok Tersedia</h5>
            </div>
            <div class="card-body">
                <?php if (count($stok) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
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
                                            <button type="button" class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $item['id']; ?>">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#hapusModal<?php echo $item['id']; ?>">
                                                <i class="fas fa-trash"></i> Hapus
                                            </button>
                                        </td>
                                    </tr>
                                    
                                    <!-- Modal Edit Stok -->
                                    <div class="modal fade" id="editModal<?php echo $item['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Stok</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" name="update_stok" class="btn btn-primary">Simpan Perubahan</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Modal Hapus Stok -->
                                    <div class="modal fade" id="hapusModal<?php echo $item['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Hapus Item Stok</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST">
                                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                    <div class="modal-body">
                                                        <p>Apakah Anda yakin ingin menghapus item <strong><?php echo $item['item_name']; ?></strong>?</p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" name="hapus_stok" class="btn btn-danger">Hapus</button>
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
                        <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
                        <p class="text-muted">Belum ada data stok.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../components/footer.php'; ?>