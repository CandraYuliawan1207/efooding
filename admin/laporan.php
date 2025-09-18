<?php
require_once '../components/functions.php';
requireAdminLogin();

// Koneksi database
require_once '../components/connect.php';
$database = new Database();
$db = $database->getConnection();

// Inisialisasi variabel filter
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_department = isset($_GET['department']) ? $_GET['department'] : '';

// Build query dengan filter
$query = "SELECT fr.*, u.username, u.department 
          FROM fooding_requests fr 
          JOIN users u ON fr.user_id = u.id 
          WHERE 1=1";

$params = [];

// Filter range tanggal
if (!empty($start_date) && !empty($end_date)) {
    $query .= " AND DATE(fr.tanggal) BETWEEN :start_date AND :end_date";
    $params[':start_date'] = $start_date;
    $params[':end_date'] = $end_date;
}

// Filter status
if (!empty($filter_status) && $filter_status != 'all') {
    $query .= " AND fr.status = :status";
    $params[':status'] = $filter_status;
}

// Filter department
if (!empty($filter_department) && $filter_department != 'all') {
    $query .= " AND u.department = :department";
    $params[':department'] = $filter_department;
}

$query .= " ORDER BY fr.tanggal DESC";

// Prepare dan execute query dengan filter
$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$laporan = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung statistik
$total_pengajuan = count($laporan);
$total_paket = array_sum(array_column($laporan, 'jumlah'));
$pengajuan_disetujui = array_filter($laporan, function ($item) {
    return $item['status'] == 'Disetujui';
});
$total_disetujui = count($pengajuan_disetujui);
$paket_disetujui = array_sum(array_column($pengajuan_disetujui, 'jumlah'));

// Hitung persentase disetujui
$persentase_disetujui = $total_pengajuan > 0 ? round(($total_disetujui / $total_pengajuan) * 100, 2) : 0;

// Ambil list department untuk filter
$deptQuery = "SELECT DISTINCT department FROM users WHERE department IS NOT NULL AND department != '' ORDER BY department";
$deptStmt = $db->prepare($deptQuery);
$deptStmt->execute();
$departments = $deptStmt->fetchAll(PDO::FETCH_COLUMN);

// Export to Excel
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="laporan_fooding_' . $start_date . '_to_' . $end_date . '.xls"');

    echo "Laporan Fooding - " . date('d/m/Y', strtotime($start_date)) . " sampai " . date('d/m/Y', strtotime($end_date)) . "\n\n";
    echo "Tanggal\tUser\tDepartment\tJumlah Paket\tStatus\n";

    foreach ($laporan as $row) {
        echo date('d/m/Y', strtotime($row['tanggal'])) . "\t";
        echo $row['username'] . "\t";
        echo $row['department'] . "\t";
        echo $row['jumlah'] . "\t";
        echo $row['status'] . "\n";
    }
    exit();
}
?>

<?php include '../components/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1">Laporan Extra Fooding</h2>
                    <p class="text-muted mb-0">Analisis dan statistik pengajuan fooding</p>
                </div>
                <div>
                    <?php if (count($laporan) > 0): ?>
                        <a href="?export=excel&<?php echo http_build_query($_GET); ?>" class="btn btn-success">
                            <i class="fas fa-file-excel me-1"></i>Export Excel
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Filter Form -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Filter Laporan</h6>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-2">
                            <label class="form-label">Start Date</label>
                            <input type="date" class="form-control" name="start_date"
                                value="<?php echo $start_date; ?>" max="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">To Date</label>
                            <input type="date" class="form-control" name="end_date"
                                value="<?php echo $end_date; ?>" max="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="all">Semua Status</option>
                                <option value="Menunggu" <?php echo $filter_status == 'Menunggu' ? 'selected' : ''; ?>>Menunggu</option>
                                <option value="Diperiksa" <?php echo $filter_status == 'Diperiksa' ? 'selected' : ''; ?>>Diperiksa</option>
                                <option value="Disetujui" <?php echo $filter_status == 'Disetujui' ? 'selected' : ''; ?>>Disetujui</option>
                                <option value="Disetujui Sebagian" <?php echo $filter_status == 'Disetujui Sebagian' ? 'selected' : ''; ?>>Disetujui Sebagian</option>
                                <option value="Ditolak" <?php echo $filter_status == 'Ditolak' ? 'selected' : ''; ?>>Ditolak</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Department</label>
                            <select class="form-select" name="department">
                                <option value="all">Semua Department</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo $filter_department == $dept ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-filter me-1"></i>Terapkan Filter
                                </button>
                                <a href="laporan.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i>
                                </a>
                            </div>
                        </div>
                    </form>

                    <!-- Info Periode -->
                    <?php if (!empty($start_date) && !empty($end_date)): ?>
                        <div class="mt-3 p-2 bg-light rounded">
                            <small class="text-muted">
                                <strong>Periode:</strong>
                                <?php echo date('d M Y', strtotime($start_date)); ?> -
                                <?php echo date('d M Y', strtotime($end_date)); ?>
                                (<?php echo count($laporan); ?> data ditemukan)
                            </small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Statistik -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white text-center">
                        <div class="card-body py-3">
                            <h6 class="card-title">Total Pengajuan</h6>
                            <h4 class="mb-0"><?php echo number_format($total_pengajuan); ?></h4>
                            <small><?php echo number_format($total_paket); ?> paket</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white text-center">
                        <div class="card-body py-3">
                            <h6 class="card-title">Disetujui</h6>
                            <h4 class="mb-0"><?php echo number_format($total_disetujui); ?></h4>
                            <small><?php echo number_format($paket_disetujui); ?> paket</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white text-center">
                        <div class="card-body py-3">
                            <h6 class="card-title">Persentase</h6>
                            <h4 class="mb-0"><?php echo $persentase_disetujui; ?>%</h4>
                            <small>Rate disetujui</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-dark text-center">
                        <div class="card-body py-3">
                            <h6 class="card-title">Periode</h6>
                            <h4 class="mb-0">
                                <?php echo date('d M', strtotime($start_date)); ?><br>
                                <small>s/d <?php echo date('d M Y', strtotime($end_date)); ?></small>
                            </h4>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (count($laporan) > 0): ?>
                <!-- Chart -->
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0"><i class="fas fa-chart-bar me-2"></i>Grafik Pengajuan</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <canvas id="statusChart" width="400" height="250"></canvas>
                            </div>
                            <div class="col-md-6">
                                <canvas id="departmentChart" width="400" height="250"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Tabel Laporan -->
            <div class="card">
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Detail Laporan</h5>
                        <small class="text-muted">
                            Menampilkan <?php echo number_format(count($laporan)); ?> data
                            (<?php echo date('d M Y', strtotime($start_date)); ?> - <?php echo date('d M Y', strtotime($end_date)); ?>)
                        </small>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (count($laporan) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-striped">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>User</th>
                                        <th>Department</th>
                                        <th>Jumlah Paket</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($laporan as $item): ?>
                                        <tr>
                                            <td><?php echo formatDateTimeIndonesian($item['tanggal']); ?></td>
                                            <td><?php echo htmlspecialchars($item['username']); ?></td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <?php echo htmlspecialchars($item['department']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary">
                                                    <?php echo number_format($item['jumlah']); ?> paket
                                                </span>
                                            </td>
                                            <td>
                                                <?php
                                                $badge_class = '';
                                                switch ($item['status']) {
                                                    case 'Menunggu':
                                                        $badge_class = 'bg-warning';
                                                        break;
                                                    case 'Diperiksa':
                                                        $badge_class = 'bg-info';
                                                        break;
                                                    case 'Disetujui':
                                                        $badge_class = 'bg-success';
                                                        break;
                                                    case 'Disetujui Sebagian':
                                                        $badge_class = 'bg-warning';
                                                        break;
                                                    case 'Ditolak':
                                                        $badge_class = 'bg-danger';
                                                        break;
                                                    default:
                                                        $badge_class = 'bg-secondary';
                                                }
                                                ?>
                                                <span class="badge <?php echo $badge_class; ?>">
                                                    <?php echo $item['status']; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-chart-line fa-4x text-muted mb-3"></i>
                            <p class="text-muted">Tidak ada data laporan untuk filter yang dipilih.</p>
                            <a href="laporan.php" class="btn btn-primary">
                                <i class="fas fa-times me-2"></i>Hapus Filter
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (count($laporan) > 0): ?>
    <!-- JavaScript untuk Chart -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Data untuk chart
        const statusData = {
            'Menunggu': <?php echo count(array_filter($laporan, fn($item) => $item['status'] === 'Menunggu')); ?>,
            'Diperiksa': <?php echo count(array_filter($laporan, fn($item) => $item['status'] === 'Diperiksa')); ?>,
            'Disetujui': <?php echo count(array_filter($laporan, fn($item) => $item['status'] === 'Disetujui')); ?>,
            'Disetujui Sebagian': <?php echo count(array_filter($laporan, fn($item) => $item['status'] === 'Disetujui Sebagian')); ?>,
            'Ditolak': <?php echo count(array_filter($laporan, fn($item) => $item['status'] === 'Ditolak')); ?>
        };

        const departmentData = {
            <?php
            $deptCounts = [];
            foreach ($laporan as $item) {
                $dept = $item['department'];
                if (!empty($dept)) {
                    $deptCounts[$dept] = ($deptCounts[$dept] ?? 0) + 1;
                }
            }
            foreach ($deptCounts as $dept => $count) {
                echo "'" . addslashes($dept) . "': $count,";
            }
            ?>
        };

        // Status Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: Object.keys(statusData),
                datasets: [{
                    data: Object.values(statusData),
                    backgroundColor: ['#ffc107', '#17a2b8', '#28a745','#fd7e14', '#dc3545']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    title: {
                        display: true,
                        text: 'Distribusi Status Pengajuan'
                    }
                }
            }
        });

        // Department Chart
        const deptCtx = document.getElementById('departmentChart').getContext('2d');
        new Chart(deptCtx, {
            type: 'bar',
            data: {
                labels: Object.keys(departmentData),
                datasets: [{
                    label: 'Jumlah Pengajuan',
                    data: Object.values(departmentData),
                    backgroundColor: '#007bff'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Pengajuan per Department'
                    }
                }
            }
        });
    </script>
<?php endif; ?>

<?php include '../components/footer.php'; ?>