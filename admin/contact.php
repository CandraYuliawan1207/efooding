<?php
require_once '../components/functions.php';
requireAdminLogin();

// Set title untuk header
$page_title = "Kelola Kontak - Admin Panel";

// Include header
include '../components/header.php';

// Koneksi database
require_once '../components/connect.php';
$database = new Database();
$db = $database->getConnection();

// Ambil data contact messages dengan pagination
$limit = 10; // Jumlah item per halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Hitung total pesan
$query_count = "SELECT COUNT(*) as total FROM contact_messages";
$stmt_count = $db->prepare($query_count);
$stmt_count->execute();
$total_messages = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_messages / $limit);

// Query dengan pagination
$query = "SELECT cm.*, u.username, u.department 
          FROM contact_messages cm 
          LEFT JOIN users u ON cm.user_id = u.id 
          ORDER BY cm.status ASC, cm.created_at DESC 
          LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($query);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung statistik
$unread_messages = 0;
$query_unread = "SELECT COUNT(*) as unread FROM contact_messages WHERE status = 'unread'";
$stmt_unread = $db->prepare($query_unread);
$stmt_unread->execute();
$unread_messages = $stmt_unread->fetch(PDO::FETCH_ASSOC)['unread'];

// Update status menjadi read jika ada parameter read
if (isset($_GET['read'])) {
    $message_id = $_GET['read'];
    $query = "UPDATE contact_messages SET status = 'read' WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $message_id);
    $stmt->execute();
    
    header("Location: contact.php?page=" . $page);
    exit();
}

// Hapus message jika ada parameter delete
if (isset($_GET['delete'])) {
    $message_id = $_GET['delete'];
    $query = "DELETE FROM contact_messages WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $message_id);
    $stmt->execute();
    
    header("Location: contact.php?page=" . $page);
    exit();
}

// Tandai semua sebagai telah dibaca
if (isset($_GET['mark_all_read'])) {
    $query = "UPDATE contact_messages SET status = 'read' WHERE status = 'unread'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    header("Location: contact.php?page=" . $page);
    exit();
}

// Filter berdasarkan kategori
$current_category = isset($_GET['category']) ? $_GET['category'] : 'all';
$category_filter = '';
if ($current_category != 'all') {
    $category_filter = " WHERE cm.kategori = :category";
}

// Filter berdasarkan status
$current_status = isset($_GET['status']) ? $_GET['status'] : 'all';
$status_filter = '';
if ($current_status != 'all') {
    if ($category_filter) {
        $status_filter = " AND cm.status = :status";
    } else {
        $status_filter = " WHERE cm.status = :status";
    }
}

// Query dengan filter
$query_filter = "SELECT cm.*, u.username, u.department 
                 FROM contact_messages cm 
                 LEFT JOIN users u ON cm.user_id = u.id" 
                 . $category_filter . $status_filter . 
                 " ORDER BY cm.status ASC, cm.created_at DESC 
                 LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($query_filter);

if ($current_category != 'all') {
    $stmt->bindValue(':category', $current_category, PDO::PARAM_STR);
}
if ($current_status != 'all') {
    $stmt->bindValue(':status', $current_status, PDO::PARAM_STR);
}

$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    .stats-card {
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        transition: transform 0.3s;
    }
    
    .stats-card:hover {
        transform: translateY(-5px);
    }
    
    .message-row {
        transition: background-color 0.2s;
        border-left: 4px solid transparent;
    }
    
    .message-row.unread {
        background-color: #f0f8ff;
        border-left-color: #3498db;
    }
    
    .message-row:hover {
        background-color: #f8f9fa;
    }
    
    .badge-technical { background-color: #6f42c1; }
    .badge-fooding { background-color: #20c997; }
    .badge-complaint { background-color: #dc3545; }
    .badge-suggestion { background-color: #fd7e14; }
    .badge-other { background-color: #6c757d; }
    
    .pagination {
        margin-bottom: 0;
    }
    
    .filter-card {
        background-color: #f8f9fa;
        border-radius: 10px;
    }
    
    .avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: #e9ecef;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .action-btn {
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
    }
    
    /* Modal styles */
    .modal-content {
        border-radius: 12px;
        border: none;
        box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    }
    
    .modal-header {
        background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
        color: white;
        border-top-left-radius: 12px;
        border-top-right-radius: 12px;
    }
    
    .message-content {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 1.5rem;
        white-space: pre-wrap;
    }
    
    .email-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        text-decoration: none;
    }
</style>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h2 fw-bold">Kelola Pesan Kontak</h1>
                    <p class="text-muted">Kelola semua pesan yang masuk dari user</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="contact.php?mark_all_read=1&page=<?php echo $page; ?>" class="btn btn-success">
                        <i class="fas fa-check-double me-1"></i> Tandai Semua Dibaca
                    </a>
                    <a href="contact.php" class="btn btn-outline-secondary">
                        <i class="fas fa-sync me-1"></i> Reset Filter
                    </a>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card stats-card bg-primary text-white">
                        <div class="card-body text-center py-4">
                            <div class="d-flex justify-content-center align-items-center mb-2">
                                <i class="fas fa-envelope fa-2x"></i>
                            </div>
                            <h6 class="card-title mb-1">Total Pesan</h6>
                            <h3 class="mb-0"><?php echo $total_messages; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card stats-card bg-warning text-dark">
                        <div class="card-body text-center py-4">
                            <div class="d-flex justify-content-center align-items-center mb-2">
                                <i class="fas fa-envelope-open fa-2x"></i>
                            </div>
                            <h6 class="card-title mb-1">Belum Dibaca</h6>
                            <h3 class="mb-0"><?php echo $unread_messages; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card stats-card bg-success text-white">
                        <div class="card-body text-center py-4">
                            <div class="d-flex justify-content-center align-items-center mb-2">
                                <i class="fas fa-check-circle fa-2x"></i>
                            </div>
                            <h6 class="card-title mb-1">Telah Dibaca</h6>
                            <h3 class="mb-0"><?php echo $total_messages - $unread_messages; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card stats-card bg-info text-white">
                        <div class="card-body text-center py-4">
                            <div class="d-flex justify-content-center align-items-center mb-2">
                                <i class="fas fa-calendar-alt fa-2x"></i>
                            </div>
                            <h6 class="card-title mb-1">Bulan Ini</h6>
                            <h3 class="mb-0">
                                <?php 
                                $query_month = "SELECT COUNT(*) as count FROM contact_messages 
                                               WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) 
                                               AND YEAR(created_at) = YEAR(CURRENT_DATE())";
                                $stmt_month = $db->prepare($query_month);
                                $stmt_month->execute();
                                echo $stmt_month->fetch(PDO::FETCH_ASSOC)['count'];
                                ?>
                            </h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="card filter-card mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3">Filter Pesan</h5>
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Kategori</label>
                            <select class="form-select" name="category">
                                <option value="all" <?php echo $current_category == 'all' ? 'selected' : ''; ?>>Semua Kategori</option>
                                <option value="technical" <?php echo $current_category == 'technical' ? 'selected' : ''; ?>>Technical Support</option>
                                <option value="fooding" <?php echo $current_category == 'fooding' ? 'selected' : ''; ?>>Pengajuan Fooding</option>
                                <option value="complaint" <?php echo $current_category == 'complaint' ? 'selected' : ''; ?>>Keluhan</option>
                                <option value="suggestion" <?php echo $current_category == 'suggestion' ? 'selected' : ''; ?>>Saran</option>
                                <option value="other" <?php echo $current_category == 'other' ? 'selected' : ''; ?>>Lainnya</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="all" <?php echo $current_status == 'all' ? 'selected' : ''; ?>>Semua Status</option>
                                <option value="unread" <?php echo $current_status == 'unread' ? 'selected' : ''; ?>>Belum Dibaca</option>
                                <option value="read" <?php echo $current_status == 'read' ? 'selected' : ''; ?>>Sudah Dibaca</option>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter me-1"></i> Terapkan Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Messages Table -->
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Daftar Pesan</h5>
                    <span class="badge bg-primary"><?php echo count($messages); ?> pesan ditampilkan</span>
                </div>
                <div class="card-body p-0">
                    <?php if (count($messages) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 25%;">Pengirim</th>
                                        <th style="width: 15%;">Kategori</th>
                                        <th style="width: 25%;">Subjek</th>
                                        <th style="width: 15%;">Tanggal</th>
                                        <th style="width: 10%;">Status</th>
                                        <th style="width: 10%;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($messages as $message): ?>
                                        <tr class="message-row <?php echo $message['status'] == 'unread' ? 'unread' : ''; ?>">
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar me-3">
                                                        <i class="fas fa-user text-primary"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold"><?php echo htmlspecialchars($message['nama']); ?></div>
                                                        <small class="text-muted">
                                                            <?php echo $message['username'] ? '@' . $message['username'] : 'Guest'; ?>
                                                            <?php echo $message['department'] ? ' • ' . $message['department'] : ''; ?>
                                                        </small>
                                                        <div>
                                                            <small class="text-primary"><?php echo htmlspecialchars($message['email']); ?></small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php 
                                                $kategori_labels = [
                                                    'technical' => 'Technical',
                                                    'fooding' => 'Fooding', 
                                                    'complaint' => 'Keluhan',
                                                    'suggestion' => 'Saran',
                                                    'other' => 'Lainnya'
                                                ];
                                                $kategori = $kategori_labels[$message['kategori']] ?? $message['kategori'];
                                                ?>
                                                <span class="badge badge-<?php echo $message['kategori']; ?> px-2 py-1">
                                                    <?php echo $kategori; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="text-truncate" style="max-width: 250px;" title="<?php echo htmlspecialchars($message['subjek']); ?>">
                                                    <?php echo htmlspecialchars($message['subjek']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div><?php echo date('d M Y', strtotime($message['created_at'])); ?></div>
                                                <small class="text-muted"><?php echo date('H:i', strtotime($message['created_at'])); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $message['status'] == 'unread' ? 'warning' : 'success'; ?>">
                                                    <?php echo $message['status'] == 'unread' ? 'Belum Dibaca' : 'Telah Dibaca'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-1">
                                                    <button type="button" class="btn btn-sm btn-outline-primary action-btn view-message" 
                                                            data-bs-toggle="modal" data-bs-target="#messageModal"
                                                            data-id="<?php echo $message['id']; ?>"
                                                            data-nama="<?php echo htmlspecialchars($message['nama']); ?>"
                                                            data-email="<?php echo htmlspecialchars($message['email']); ?>"
                                                            data-username="<?php echo htmlspecialchars($message['username'] ?? 'Guest'); ?>"
                                                            data-department="<?php echo htmlspecialchars($message['department'] ?? '-'); ?>"
                                                            data-kategori="<?php echo $kategori; ?>"
                                                            data-subjek="<?php echo htmlspecialchars($message['subjek']); ?>"
                                                            data-pesan="<?php echo htmlspecialchars($message['pesan']); ?>"
                                                            data-tanggal="<?php echo date('d M Y H:i', strtotime($message['created_at'])); ?>"
                                                            title="Lihat Detail">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <a href="mailto:<?php echo htmlspecialchars($message['email']); ?>?subject=Balas: <?php echo urlencode($message['subjek']); ?>" 
                                                       class="btn btn-sm btn-outline-info action-btn" title="Balas via Email">
                                                        <i class="fas fa-reply"></i>
                                                    </a>
                                                    <?php if ($message['status'] == 'unread'): ?>
                                                    <a href="contact.php?read=<?php echo $message['id']; ?>&page=<?php echo $page; ?>" 
                                                       class="btn btn-sm btn-outline-success action-btn" title="Tandai Sudah Dibaca">
                                                        <i class="fas fa-check"></i>
                                                    </a>
                                                    <?php endif; ?>
                                                    <a href="contact.php?delete=<?php echo $message['id']; ?>&page=<?php echo $page; ?>" 
                                                       class="btn btn-sm btn-outline-danger action-btn"
                                                       onclick="return confirm('Hapus pesan ini? Tindakan ini tidak dapat dibatalkan.')"
                                                       title="Hapus Pesan">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">Tidak ada pesan</h5>
                            <p class="text-muted">Tidak ditemukan pesan yang sesuai dengan filter Anda</p>
                            <a href="contact.php" class="btn btn-primary">
                                <i class="fas fa-sync me-1"></i> Reset Filter
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="card-footer">
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center mb-0">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="contact.php?page=<?php echo $page - 1; ?><?php echo $current_category != 'all' ? '&category=' . $current_category : ''; ?><?php echo $current_status != 'all' ? '&status=' . $current_status : ''; ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="contact.php?page=<?php echo $i; ?><?php echo $current_category != 'all' ? '&category=' . $current_category : ''; ?><?php echo $current_status != 'all' ? '&status=' . $current_status : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="contact.php?page=<?php echo $page + 1; ?><?php echo $current_category != 'all' ? '&category=' . $current_category : ''; ?><?php echo $current_status != 'all' ? '&status=' . $current_status : ''; ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal untuk melihat pesan -->
<div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="messageModalLabel">Detail Pesan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Pengirim</label>
                            <p id="modal-nama" class="mb-0"></p>
                            <small id="modal-email" class="text-muted"></small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Username & Departemen</label>
                            <p id="modal-userinfo" class="mb-0"></p>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Kategori</label>
                            <p id="modal-kategori" class="mb-0"></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Tanggal</label>
                            <p id="modal-tanggal" class="mb-0"></p>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Subjek</label>
                    <p id="modal-subjek" class="fw-semibold"></p>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Pesan</label>
                    <div id="modal-pesan" class="message-content"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <a href="#" id="modal-reply-btn" class="btn btn-primary email-button">
                    <i class="fas fa-reply me-1"></i> Balas via Email
                </a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle modal view
    const viewButtons = document.querySelectorAll('.view-message');
    const messageModal = new bootstrap.Modal(document.getElementById('messageModal'));
    
    viewButtons.forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const nama = this.getAttribute('data-nama');
            const email = this.getAttribute('data-email');
            const username = this.getAttribute('data-username');
            const department = this.getAttribute('data-department');
            const kategori = this.getAttribute('data-kategori');
            const subjek = this.getAttribute('data-subjek');
            const pesan = this.getAttribute('data-pesan');
            const tanggal = this.getAttribute('data-tanggal');
            
            // Set modal content
            document.getElementById('modal-nama').textContent = nama;
            document.getElementById('modal-email').textContent = email;
            document.getElementById('modal-userinfo').textContent = `${username} • ${department}`;
            document.getElementById('modal-kategori').textContent = kategori;
            document.getElementById('modal-tanggal').textContent = tanggal;
            document.getElementById('modal-subjek').textContent = subjek;
            document.getElementById('modal-pesan').textContent = pesan;
            
            // Set reply link
            const replyBtn = document.getElementById('modal-reply-btn');
            replyBtn.href = `mailto:${email}?subject=Balas: ${encodeURIComponent(subjek)}&body=Halo ${encodeURIComponent(nama)},\n\nTerima kasih telah menghubungi kami.`;
            
            // Show modal
            messageModal.show();
        });
    });
});
</script>

<?php
// Include footer
include '../components/footer.php';
?>