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

// Build query dengan filter - MENGGUNAKAN tanggal BUKAN created_at
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

// Filter tanggal dari - MENGGUNAKAN tanggal BUKAN created_at
if (!empty($filter_dari)) {
    $query .= " AND DATE(fr.tanggal) >= :dari_tanggal";
    $params[':dari_tanggal'] = $filter_dari;
}

// Filter tanggal sampai - MENGGUNAKAN tanggal BUKAN created_at
if (!empty($filter_sampai)) {
    $query .= " AND DATE(fr.tanggal) <= :sampai_tanggal";
    $params[':sampai_tanggal'] = $filter_sampai;
}

$query .= " ORDER BY fr.tanggal DESC"; // MENGGUNAKAN tanggal BUKAN created_at

// Prepare dan execute query dengan filter
$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$pengajuan = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Proses update status pengajuan
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_status'])) {
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

            // 2. Dapatkan user_id dan jumlah dari pengajuan
            $query = "SELECT user_id, jumlah FROM fooding_requests WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $request_id);
            $stmt->execute();
            $pengajuan_data = $stmt->fetch(PDO::FETCH_ASSOC);
            $user_id = $pengajuan_data['user_id'];
            $jumlah = $pengajuan_data['jumlah'];

            // 3. Buat notifikasi untuk user
            $message = "";
            switch ($new_status) {
                case 'Diperiksa':
                    $message = "Pengajuan fooding " . $jumlah . " paket sedang diperiksa oleh admin";
                    break;
                case 'Disetujui':
                    $message = "Pengajuan fooding " . $jumlah . " paket telah disetujui. Silahkan ambil ke Waserda";

                    // Kurangi stok jika disetujui
                    $query = "UPDATE stock SET quantity = quantity - :jumlah WHERE item_name = 'Indomie'";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':jumlah', $jumlah);
                    $stmt->execute();

                    $query = "UPDATE stock SET quantity = quantity - :jumlah WHERE item_name = 'Kopi'";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':jumlah', $jumlah);
                    $stmt->execute();
                    break;
                case 'Ditolak':
                    $message = "Pengajuan fooding " . $jumlah . " paket ditolak. Hubungi admin untuk informasi lebih lanjut";
                    break;
                default:
                    $message = "Status pengajuan fooding diubah menjadi: " . $new_status;
            }

            $notifQuery = "INSERT INTO notifications (user_id, message, status, timestamp) 
                           VALUES (:user_id, :message, 'unread', NOW())";
            $notifStmt = $db->prepare($notifQuery);
            $notifStmt->bindParam(':user_id', $user_id);
            $notifStmt->bindParam(':message', $message);
            $notifStmt->execute();

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

    // Proses update status individual penerima
    if (isset($_POST['update_recipient_status'])) {
        $recipient_id = $_POST['recipient_id'];
        $request_id = $_POST['request_id'];
        $status = $_POST['recipient_status'];

        try {
            // Mulai transaction
            $db->beginTransaction();

            // 1. Update status penerima individual
            $query = "UPDATE fooding_recipients SET status = :status WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':id', $recipient_id);
            $stmt->execute();

            // 2. Hitung jumlah penerima yang disetujui dan ditolak
            $query = "SELECT 
                        COUNT(CASE WHEN status = 'Disetujui' THEN 1 END) as disetujui,
                        COUNT(CASE WHEN status = 'Ditolak' THEN 1 END) as ditolak,
                        COUNT(CASE WHEN status = 'Menunggu' THEN 1 END) as menunggu
                      FROM fooding_recipients WHERE request_id = :request_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':request_id', $request_id);
            $stmt->execute();
            $status_count = $stmt->fetch(PDO::FETCH_ASSOC);

            $disetujui = $status_count['disetujui'];
            $ditolak = $status_count['ditolak'];
            $menunggu = $status_count['menunggu'];

            // 3. Update status pengajuan berdasarkan status penerima
            $new_request_status = 'Diperiksa';
            $jumlah_disetujui = $disetujui;

            if ($menunggu == 0) {
                // Semua penerima sudah diproses
                if ($ditolak == 0) {
                    $new_request_status = 'Disetujui';
                } elseif ($disetujui == 0) {
                    $new_request_status = 'Ditolak';
                } else {
                    $new_request_status = 'Disetujui Sebagian';
                }

                // Update jumlah yang disetujui di fooding_requests
                $query = "UPDATE fooding_requests SET status = :status, jumlah_disetujui = :jumlah_disetujui WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':status', $new_request_status);
                $stmt->bindParam(':jumlah_disetujui', $jumlah_disetujui);
                $stmt->bindParam(':id', $request_id);
                $stmt->execute();

                // Kurangi stok jika ada yang disetujui
                if ($disetujui > 0) {
                    $query = "UPDATE stock SET quantity = quantity - :jumlah WHERE item_name = 'Indomie'";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':jumlah', $disetujui);
                    $stmt->execute();

                    $query = "UPDATE stock SET quantity = quantity - :jumlah WHERE item_name = 'Kopi'";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':jumlah', $disetujui);
                    $stmt->execute();
                }

                // Buat notifikasi untuk user
                $query = "SELECT user_id, jumlah FROM fooding_requests WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $request_id);
                $stmt->execute();
                $pengajuan_data = $stmt->fetch(PDO::FETCH_ASSOC);
                $user_id = $pengajuan_data['user_id'];
                $jumlah = $pengajuan_data['jumlah'];

                $message = "Pengajuan fooding Anda telah diproses. ";
                if ($new_request_status == 'Disetujui') {
                    $message .= "Semua $jumlah paket disetujui.";
                } elseif ($new_request_status == 'Ditolak') {
                    $message .= "Semua $jumlah paket ditolak.";
                } else {
                    $message .= "$disetujui dari $jumlah paket disetujui.";
                }

                $notifQuery = "INSERT INTO notifications (user_id, message, status, timestamp) 
                               VALUES (:user_id, :message, 'unread', NOW())";
                $notifStmt = $db->prepare($notifQuery);
                $notifStmt->bindParam(':user_id', $user_id);
                $notifStmt->bindParam(':message', $message);
                $notifStmt->execute();
            } else {
                // Masih ada penerima yang menunggu, update status menjadi Diperiksa
                $query = "UPDATE fooding_requests SET status = :status WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':status', $new_request_status);
                $stmt->bindParam(':id', $request_id);
                $stmt->execute();
            }

            // Commit transaction
            $db->commit();

            setNotification('Status penerima berhasil diperbarui', 'success');
            header("Location: manage.php?view=" . $request_id);
            exit();
        } catch (Exception $e) {
            // Rollback transaction jika ada error
            $db->rollBack();
            setNotification('Gagal memperbarui status penerima: ' . $e->getMessage(), 'error');
            header("Location: manage.php?view=" . $request_id);
            exit();
        }
    }
}

// Ambil detail penerima jika parameter view ada
$recipients = [];
$pengajuan_detail = [];
if (isset($_GET['view'])) {
    $request_id = $_GET['view'];
    $query = "SELECT * FROM fooding_recipients WHERE request_id = :request_id ORDER BY name";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':request_id', $request_id);
    $stmt->execute();
    $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ambil info pengajuan
    $query = "SELECT fr.*, u.username, u.department 
              FROM fooding_requests fr 
              JOIN users u ON fr.user_id = u.id 
              WHERE fr.id = :request_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':request_id', $request_id);
    $stmt->execute();
    $pengajuan_detail = $stmt->fetch(PDO::FETCH_ASSOC);

    // Hitung statistik penerima
    $stats = [
        'disetujui' => 0,
        'ditolak' => 0,
        'menunggu' => 0,
        'total' => count($recipients)
    ];

    foreach ($recipients as $recipient) {
        if ($recipient['status'] == 'Disetujui') $stats['disetujui']++;
        if ($recipient['status'] == 'Ditolak') $stats['ditolak']++;
        if ($recipient['status'] == 'Menunggu') $stats['menunggu']++;
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

        .badge-menunggu {
            background-color: #ffc107;
            color: #000;
        }

        .badge-diperiksa {
            background-color: #0dcaf0;
            color: #000;
        }

        .badge-disetujui {
            background-color: #198754;
            color: #fff;
        }

        .badge-ditolak {
            background-color: #dc3545;
            color: #fff;
        }

        .badge-disetujui-sebagian {
            background-color: #fd7e14;
            color: #fff;
        }

        .stat-card {
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>

<body>
    <?php include '../components/header.php'; ?>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <?php if (isset($_GET['view'])): ?>
                    <!-- Detail View untuk Melihat Penerima -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2 class="mb-1">Detail Pengajuan #<?php echo $pengajuan_detail['id']; ?></h2>
                            <p class="text-muted mb-0">Daftar penerima fooding</p>
                        </div>
                        <div>
                            <a href="manage.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Kembali
                            </a>
                        </div>
                    </div>

                    <!-- Statistik Penerima -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white text-center stat-card">
                                <div class="card-body py-3">
                                    <h6 class="card-title">Total Penerima</h6>
                                    <h4 class="mb-0"><?php echo $stats['total']; ?></h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white text-center stat-card">
                                <div class="card-body py-3">
                                    <h6 class="card-title">Disetujui</h6>
                                    <h4 class="mb-0"><?php echo $stats['disetujui']; ?></h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-danger text-white text-center stat-card">
                                <div class="card-body py-3">
                                    <h6 class="card-title">Ditolak</h6>
                                    <h4 class="mb-0"><?php echo $stats['ditolak']; ?></h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-dark text-center stat-card">
                                <div class="card-body py-3">
                                    <h6 class="card-title">Menunggu</h6>
                                    <h4 class="mb-0"><?php echo $stats['menunggu']; ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Info Pengajuan -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informasi Pengajuan</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <strong>User:</strong> <?php echo htmlspecialchars($pengajuan_detail['username']); ?>
                                </div>
                                <div class="col-md-3">
                                    <strong>Departemen:</strong> <?php echo htmlspecialchars($pengajuan_detail['department']); ?>
                                </div>
                                <div class="col-md-3">
                                    <strong>Jumlah Diajukan:</strong> <?php echo $pengajuan_detail['jumlah']; ?> paket
                                </div>
                                <div class="col-md-3">
                                    <strong>Jumlah Disetujui:</strong>
                                    <?php if (isset($pengajuan_detail['jumlah_disetujui'])): ?>
                                        <?php echo $pengajuan_detail['jumlah_disetujui']; ?> paket
                                    <?php else: ?>
                                        <span class="text-muted">Belum diproses</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <strong>Status Pengajuan:</strong>
                                    <?php
                                    $statusClass = '';
                                    switch ($pengajuan_detail['status']) {
                                        case 'Menunggu':
                                            $statusClass = 'badge-menunggu';
                                            break;
                                        case 'Diperiksa':
                                            $statusClass = 'badge-diperiksa';
                                            break;
                                        case 'Disetujui':
                                            $statusClass = 'badge-disetujui';
                                            break;
                                        case 'Ditolak':
                                            $statusClass = 'badge-ditolak';
                                            break;
                                        case 'Disetujui Sebagian':
                                            $statusClass = 'badge-disetujui-sebagian';
                                            break;
                                        default:
                                            $statusClass = 'badge-secondary';
                                    }
                                    ?>
                                    <span class="badge <?php echo $statusClass; ?>">
                                        <?php echo $pengajuan_detail['status']; ?>
                                    </span>
                                </div>
                                <div class="col-md-6">
                                    <strong>Tanggal Pengajuan:</strong> <?php echo date('d M Y H:i', strtotime($pengajuan_detail['tanggal'])); ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Daftar Penerima -->
                    <div class="card">
                        <div class="card-header bg-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Daftar Penerima Voucher</h5>
                                <small class="text-muted">
                                    Klik tombol Setujui/Tolak untuk mengubah status setiap penerima
                                </small>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (count($recipients) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>No</th>
                                                <th>Nama Penerima</th>
                                                <th>Status</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recipients as $index => $recipient): ?>
                                                <tr>
                                                    <td><?php echo $index + 1; ?></td>
                                                    <td><?php echo htmlspecialchars($recipient['name']); ?></td>
                                                    <td>
                                                        <?php
                                                        $recipientStatusClass = '';
                                                        switch ($recipient['status']) {
                                                            case 'Menunggu':
                                                                $recipientStatusClass = 'badge-menunggu';
                                                                break;
                                                            case 'Disetujui':
                                                                $recipientStatusClass = 'badge-disetujui';
                                                                break;
                                                            case 'Ditolak':
                                                                $recipientStatusClass = 'badge-ditolak';
                                                                break;
                                                            default:
                                                                $recipientStatusClass = 'badge-secondary';
                                                        }
                                                        ?>
                                                        <span class="badge <?php echo $recipientStatusClass; ?>">
                                                            <?php echo $recipient['status']; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="recipient_id" value="<?php echo $recipient['id']; ?>">
                                                            <input type="hidden" name="request_id" value="<?php echo $request_id; ?>">
                                                            <input type="hidden" name="recipient_status" value="">
                                                            <div class="btn-group">
                                                                <button type="button" onclick="setRecipientStatus(this, 'Disetujui')"
                                                                    class="btn btn-sm <?php echo $recipient['status'] == 'Disetujui' ? 'btn-success' : 'btn-outline-success'; ?>">
                                                                    <i class="fas fa-check me-1"></i>Setujui
                                                                </button>
                                                                <button type="button" onclick="setRecipientStatus(this, 'Ditolak')"
                                                                    class="btn btn-sm <?php echo $recipient['status'] == 'Ditolak' ? 'btn-danger' : 'btn-outline-danger'; ?>">
                                                                    <i class="fas fa-times me-1"></i>Tolak
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Info Penting -->
                                <div class="alert alert-info mt-3">
                                    <h6><i class="fas fa-info-circle me-2"></i>Informasi Penting</h6>
                                    <ul class="mb-0">
                                        <li>Stok akan otomatis berkurang sesuai jumlah penerima yang disetujui</li>
                                        <li>Status pengajuan akan berubah otomatis setelah semua penerima diproses</li>
                                        <li>User akan menerima notifikasi tentang hasil pengajuan</li>
                                    </ul>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-users fa-4x text-muted mb-3"></i>
                                    <p class="text-muted">Tidak ada data penerima untuk pengajuan ini.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- List View untuk Semua Pengajuan -->
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
                                                        <div><?php
                                                                echo formatDateIndonesian($p['tanggal']);
                                                                ?></div>
                                                        <small class="text-muted">
                                                            <?php echo date('H:i', strtotime($p['tanggal'])); ?>
                                                        </small>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($p['username']); ?></td>
                                                    <td><?php echo htmlspecialchars($p['department']); ?></td>
                                                    <td>
                                                        <span class="badge bg-secondary"><?php echo $p['jumlah']; ?> paket</span>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $badge_class = 'badge-' . strtolower($p['status']);
                                                        ?>
                                                        <span class="badge <?php echo $badge_class; ?>"><?php echo $p['status']; ?></span>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <a href="manage.php?view=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                                <i class="fas fa-eye me-1"></i>Lihat Penerima
                                                            </a>
                                                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $p['id']; ?>">
                                                                <i class="fas fa-edit me-1"></i>Ubah Status
                                                            </button>
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

                    <!-- Modal untuk Ubah Status -->
                    <?php foreach ($pengajuan as $p): ?>
                        <div class="modal fade" id="editModal<?php echo $p['id']; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Ubah Status Pengajuan</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" name="update_status" class="btn btn-primary">Simpan Perubahan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include '../components/footer.php'; ?>


    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Toggle filter form
        document.getElementById('filterToggle')?.addEventListener('click', function() {
            const filterForm = document.getElementById('filterForm');
            if (filterForm) {
                filterForm.classList.toggle('d-none');
            }
        });

        // Auto-close alerts setelah 5 detik
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // Fungsi untuk set status penerima - DIPERBAIKI
        function setRecipientStatus(button, status) {
            if (confirm(`Apakah Anda yakin ingin ${status.toLowerCase()} penerima ini?`)) {
                const form = button.closest('form');
                const statusInput = form.querySelector('input[name="recipient_status"]');
                statusInput.value = status;

                // Submit form
                form.submit();
            }
        }
    </script>

</body>

</html>