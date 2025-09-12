<?php
require_once '../components/functions.php';
requireAdminLogin();

// Koneksi database
require_once '../components/connect.php';
$database = new Database();
$db = $database->getConnection();

// Ambil data statistik
// Total pengajuan hari ini
$query = "SELECT COUNT(*) as total_hari_ini FROM fooding_requests WHERE DATE(tanggal) = CURDATE()";
$stmt = $db->prepare($query);
$stmt->execute();
$total_hari_ini = $stmt->fetch(PDO::FETCH_ASSOC)['total_hari_ini'];

// Total pengajuan minggu ini
$query = "SELECT COUNT(*) as total_minggu_ini FROM fooding_requests WHERE YEARWEEK(tanggal) = YEARWEEK(CURDATE())";
$stmt = $db->prepare($query);
$stmt->execute();
$total_minggu_ini = $stmt->fetch(PDO::FETCH_ASSOC)['total_minggu_ini'];

// Total pengajuan bulan ini
$query = "SELECT COUNT(*) as total_bulan_ini FROM fooding_requests WHERE MONTH(tanggal) = MONTH(CURDATE()) AND YEAR(tanggal) = YEAR(CURDATE())";
$stmt = $db->prepare($query);
$stmt->execute();
$total_bulan_ini = $stmt->fetch(PDO::FETCH_ASSOC)['total_bulan_ini'];

// Data untuk grafik tren pengajuan 7 hari terakhir
$query = "SELECT DATE(tanggal) as tanggal, COUNT(*) as jumlah 
          FROM fooding_requests 
          WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
          GROUP BY DATE(tanggal) 
          ORDER BY tanggal";
$stmt = $db->prepare($query);
$stmt->execute();
$data_tren = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Data pengajuan terbaru
$query = "SELECT fr.*, u.username, u.department 
          FROM fooding_requests fr 
          JOIN users u ON fr.user_id = u.id 
          ORDER BY fr.tanggal DESC 
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$pengajuan_terbaru = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Data stok
$query = "SELECT * FROM stock ORDER BY item_name";
$stmt = $db->prepare($query);
$stmt->execute();
$stok = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include '../components/header.php'; ?>
<div class="row">
    <div class="col-md-12">
        <div class="welcome-card card bg-gradient-primary text-white mb-4 fade-in">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2 class="card-title">Selamat Datang, Admin <?php echo $_SESSION['admin_username']; ?>!</h2>
                        <p class="card-text">Anda login sebagai administrator sistem E-Fooding. Kelola pengajuan, stok, dan lihat laporan.</p>
                    </div>
                    <div class="col-md-4 text-center">
                        <i class="fas fa-user-cog fa-4x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row fade-in">
    <div class="col-md-12">
        <h3 class="mb-4">Statistik Pengajuan</h3>
    </div>
</div>

<div class="row fade-in">
    <!-- Statistik Card -->
    <div class="col-md-4 mb-4">
        <div class="card stat-card bg-primary text-white smooth-hover">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h5 class="card-title">Hari Ini</h5>
                        <h2 class="card-stat"><?php echo $total_hari_ini; ?></h2>
                        <p class="card-desc">Pengajuan fooding</p>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="fas fa-calendar-day fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card stat-card bg-success text-white smooth-hover">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h5 class="card-title">Minggu Ini</h5>
                        <h2 class="card-stat"><?php echo $total_minggu_ini; ?></h2>
                        <p class="card-desc">Pengajuan fooding</p>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="fas fa-calendar-week fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card stat-card bg-info text-white smooth-hover">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h5 class="card-title">Bulan Ini</h5>
                        <h2 class="card-stat"><?php echo $total_bulan_ini; ?></h2>
                        <p class="card-desc">Pengajuan fooding</p>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="fas fa-calendar-alt fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row fade-in">
    <div class="col-md-8">
        <!-- Grafik Tren Pengajuan -->
        <div class="card smooth-hover">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0"><i class="fas fa-chart-line me-2"></i>Grafik Pengajuan 7 Hari Terakhir</h5>
            </div>
            <div class="card-body">
                <canvas id="trenChart" height="250"></canvas>
            </div>
        </div>
        
        <!-- Pengajuan Terbaru -->
        <div class="card smooth-hover mt-4">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0"><i class="fas fa-history me-2"></i>Pengajuan Terbaru</h5>
            </div>
            <div class="card-body">
                <?php if (count($pengajuan_terbaru) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>User</th>
                                    <th>Departemen</th>
                                    <th>Jumlah</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pengajuan_terbaru as $pengajuan): ?>
                                    <tr>
                                        <td><?php echo date('d M Y H:i', strtotime($pengajuan['tanggal'])); ?></td>
                                        <td><?php echo $pengajuan['username']; ?></td>
                                        <td><?php echo $pengajuan['department']; ?></td>
                                        <td><?php echo $pengajuan['jumlah']; ?> paket</td>
                                        <td>
                                            <?php 
                                            $badge_class = '';
                                            switch ($pengajuan['status']) {
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
                                            <span class="badge <?php echo $badge_class; ?>"><?php echo $pengajuan['status']; ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Belum ada pengajuan fooding.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <!-- Aksi Cepat -->
        <div class="card smooth-hover">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0"><i class="fas fa-bolt me-2"></i>Aksi Cepat</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="manage.php" class="btn btn-primary mb-3 py-3">
                        <i class="fas fa-tasks me-2"></i>Kelola Pengajuan
                    </a>
                    <a href="stock.php" class="btn btn-success mb-3 py-3">
                        <i class="fas fa-boxes me-2"></i>Kelola Stok
                    </a>
                    <a href="laporan.php" class="btn btn-info mb-3 py-3">
                        <i class="fas fa-chart-bar me-2"></i>Lihat Laporan
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Info Stok -->
        <div class="card smooth-hover mt-4">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0"><i class="fas fa-box me-2"></i>Status Stok</h5>
            </div>
            <div class="card-body">
                <?php if (count($stok) > 0): ?>
                    <div class="list-group">
                        <?php foreach ($stok as $item): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0"><?php echo $item['item_name']; ?></h6>
                                    <small class="text-muted"><?php echo $item['quantity'] . ' ' . $item['unit']; ?></small>
                                </div>
                                <span class="badge <?php echo $item['quantity'] > 20 ? 'bg-success' : ($item['quantity'] > 5 ? 'bg-warning' : 'bg-danger'); ?>">
                                    <?php echo $item['quantity'] > 20 ? 'Aman' : ($item['quantity'] > 5 ? 'Sedang' : 'Kritis'); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Data stok belum tersedia.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Grafik Tren Pengajuan
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('trenChart').getContext('2d');
    const trenData = <?php echo json_encode($data_tren); ?>;
    
    // Siapkan labels dan data
    const labels = [];
    const data = [];
    
    // Generate dates for the last 7 days
    for (let i = 6; i >= 0; i--) {
        const date = new Date();
        date.setDate(date.getDate() - i);
        const dateString = date.toISOString().split('T')[0];
        
        labels.push(new Date(dateString).toLocaleDateString('id-ID', { 
            day: 'numeric', 
            month: 'short' 
        }));
        
        // Cari data untuk tanggal ini
        const found = trenData.find(item => item.tanggal === dateString);
        data.push(found ? parseInt(found.jumlah) : 0);
    }
    
    // Buat grafik
    const trenChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Jumlah Pengajuan',
                data: data,
                backgroundColor: 'rgba(67, 97, 238, 0.1)',
                borderColor: '#4361ee',
                borderWidth: 2,
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
});
</script>

<?php include '../components/footer.php'; ?>