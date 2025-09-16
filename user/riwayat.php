<?php
require_once '../components/functions.php';
requireLogin();

// Koneksi database
require_once '../components/connect.php';
$database = new Database();
$db = $database->getConnection();

// Ambil data riwayat pengajuan user
$user_id = $_SESSION['user_id'];

// Filter parameters
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_dari = isset($_GET['dari_tanggal']) ? $_GET['dari_tanggal'] : '';
$filter_sampai = isset($_GET['sampai_tanggal']) ? $_GET['sampai_tanggal'] : '';

// Build query dengan filter
$query = "SELECT * FROM fooding_requests WHERE user_id = :user_id";
$params = [':user_id' => $user_id];

if (!empty($filter_status)) {
    $query .= " AND status = :status";
    $params[':status'] = $filter_status;
}

if (!empty($filter_dari)) {
    $query .= " AND DATE(tanggal) >= :dari_tanggal";
    $params[':dari_tanggal'] = $filter_dari;
}

if (!empty($filter_sampai)) {
    $query .= " AND DATE(tanggal) <= :sampai_tanggal";
    $params[':sampai_tanggal'] = $filter_sampai;
}

$query .= " ORDER BY tanggal DESC";

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$riwayat = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung statistik
$query_stats = "SELECT status, COUNT(*) as jumlah FROM fooding_requests WHERE user_id = :user_id GROUP BY status";
$stmt_stats = $db->prepare($query_stats);
$stmt_stats->bindParam(':user_id', $user_id);
$stmt_stats->execute();
$stats = $stmt_stats->fetchAll(PDO::FETCH_ASSOC);

// Siapkan data untuk chart
$status_labels = [];
$status_data = [];
$status_colors = [];

foreach ($stats as $stat) {
    $status_labels[] = $stat['status'];
    $status_data[] = $stat['jumlah'];

    // Tentukan warna berdasarkan status
    switch ($stat['status']) {
        case 'Disetujui':
            $status_colors[] = '#4cc9f0';
            break;
        case 'Menunggu':
            $status_colors[] = '#f9c74f';
            break;
        case 'Diperiksa':
            $status_colors[] = '#4361ee';
            break;
        case 'Ditolak':
            $status_colors[] = '#f94144';
            break;
        default:
            $status_colors[] = '#adb5bd';
    }
}
?>

<?php include '../components/header.php'; ?>
<div class="row">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Riwayat Pengajuan Fooding</h2>
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
                            <option value="Menunggu" <?php echo $filter_status == 'Menunggu' ? 'selected' : ''; ?>>Menunggu</option>
                            <option value="Diperiksa" <?php echo $filter_status == 'Diperiksa' ? 'selected' : ''; ?>>Diperiksa</option>
                            <option value="Disetujui" <?php echo $filter_status == 'Disetujui' ? 'selected' : ''; ?>>Disetujui</option>
                            <option value="Ditolak" <?php echo $filter_status == 'Ditolak' ? 'selected' : ''; ?>>Ditolak</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tanggal Dari</label>
                        <input type="date" class="form-control" name="dari_tanggal" value="<?php echo $filter_dari; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tanggal Sampai</label>
                        <input type="date" class="form-control" name="sampai_tanggal" value="<?php echo $filter_sampai; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div>
                            <button type="submit" class="btn btn-primary">Terapkan Filter</button>
                            <a href="riwayat.php" class="btn btn-secondary">Reset</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Statistik Ringkas -->
        <div class="row mb-4">
            <?php foreach ($stats as $stat): ?>
                <div class="col-md-3 mb-3">
                    <div class="card stat-card 
                        <?php echo $stat['status'] == 'Disetujui' ? 'bg-success' : ($stat['status'] == 'Menunggu' ? 'bg-warning' : ($stat['status'] == 'Diperiksa' ? 'bg-info' : 'bg-danger')); ?> 
                        text-white text-center">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo $stat['status']; ?></h5>
                            <h2 class="card-stat"><?php echo $stat['jumlah']; ?></h2>
                            <p class="card-desc">Pengajuan</p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Grafik Distribusi Status -->
        <?php if (!empty($stats)): ?>
            <div class="card mb-4 smooth-hover">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0"><i class="fas fa-chart-pie me-2"></i>Distribusi Status Pengajuan</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <canvas id="statusChart" height="250"></canvas>
                        </div>
                        <div class="col-md-6">
                            <div class="mt-4">
                                <?php foreach ($stats as $index => $stat): ?>
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="color-badge me-2"
                                            style="background-color: <?php echo $status_colors[$index]; ?>; width: 20px; height: 20px; border-radius: 4px;"></div>
                                        <span class="me-2"><?php echo $stat['status']; ?>:</span>
                                        <strong><?php echo $stat['jumlah']; ?> pengajuan</strong>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Tabel Riwayat -->
        <div class="card smooth-hover">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0"><i class="fas fa-history me-2"></i>Daftar Pengajuan</h5>
            </div>
            <div class="card-body">
                <?php if (count($riwayat) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Jumlah Paket</th>
                                    <th>Status</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($riwayat as $r): ?>
                                    <tr>
                                        <td>
                                            <?php echo formatDateTimeIndonesian($r['tanggal']); ?>
                                        </td>
                                        <td><?php echo $r['jumlah']; ?> paket</td>
                                        <td>
                                            <?php
                                            $badge_class = '';
                                            switch ($r['status']) {
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
                                            <span class="badge <?php echo $badge_class; ?>"><?php echo $r['status']; ?></span>
                                        </td>
                                        <td>
                                            <?php if ($r['status'] == 'Disetujui'): ?>
                                                <span class="text-success"><i class="fas fa-check-circle me-1"></i>Dapat diambil di waserda</span>
                                            <?php elseif ($r['status'] == 'Ditolak'): ?>
                                                <span class="text-danger"><i class="fas fa-times-circle me-1"></i>Silakan hubungi admin</span>
                                            <?php else: ?>
                                                <span class="text-muted"><i class="fas fa-clock me-1"></i>Dalam proses</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                        <p class="text-muted">Belum ada riwayat pengajuan fooding.</p>
                        <a href="ajukan.php" class="btn btn-primary mt-2">
                            <i class="fas fa-plus me-2"></i>Ajukan Fooding Pertama
                        </a>
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

    // Grafik Pie Status
    <?php if (!empty($stats)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('statusChart').getContext('2d');
            const statusChart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: <?php echo json_encode($status_labels); ?>,
                    datasets: [{
                        data: <?php echo json_encode($status_data); ?>,
                        backgroundColor: <?php echo json_encode($status_colors); ?>,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 15,
                                padding: 15
                            }
                        }
                    }
                }
            });
        });
    <?php endif; ?>
</script>

<?php include '../components/footer.php'; ?>