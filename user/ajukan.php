<?php
require_once '../components/functions.php';
requireLogin();

// Koneksi database
require_once '../components/connect.php';
$database = new Database();
$db = $database->getConnection();

// Ambil data stok untuk validasi
$query = "SELECT * FROM stock WHERE item_name IN ('Indomie', 'Kopi')";
$stmt = $db->prepare($query);
$stmt->execute();
$stok = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Siapkan data stok
$stok_indomie = 0;
$stok_kopi = 0;

foreach ($stok as $item) {
    if ($item['item_name'] == 'Indomie') {
        $stok_indomie = $item['quantity'];
    } elseif ($item['item_name'] == 'Kopi') {
        $stok_kopi = $item['quantity'];
    }
}

// Hitung maksimal paket yang bisa diajukan (terbatas oleh stok)
$max_paket = min(floor($stok_indomie / 1), floor($stok_kopi / 1));

// Proses pengajuan fooding
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajukan_fooding'])) {
    $jumlah = (int)$_POST['jumlah'];
    $nama_penerima = $_POST['nama_penerima'];

    // Validasi jumlah
    if ($jumlah <= 0) {
        setNotification('Jumlah paket harus lebih dari 0', 'error');
    } elseif ($jumlah > $max_paket) {
        setNotification('Stok tidak mencukupi. Maksimal ' . $max_paket . ' paket', 'error');
    } else {
        // Validasi nama penerima
        $valid_nama = true;
        $error_messages = [];

        // Cek apakah jumlah nama sesuai dengan jumlah paket
        if (count($nama_penerima) !== $jumlah) {
            $valid_nama = false;
            $error_messages[] = 'Jumlah nama penerima harus sama dengan jumlah paket';
        }

        // Cek apakah ada nama yang kosong
        foreach ($nama_penerima as $index => $nama) {
            if (empty(trim($nama))) {
                $valid_nama = false;
                $error_messages[] = 'Nama penerima ke-' . ($index + 1) . ' tidak boleh kosong';
            }
        }

        if (!$valid_nama) {
            setNotification(implode('<br>', $error_messages), 'error');
        } else {
            try {
                // Mulai transaction untuk atomic operation
                $db->beginTransaction();

                // Simpan pengajuan ke database
                $user_id = $_SESSION['user_id'];
                $status = 'Menunggu';

                $query = "INSERT INTO fooding_requests (user_id, jumlah, status) VALUES (:user_id, :jumlah, :status)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':jumlah', $jumlah);
                $stmt->bindParam(':status', $status);
                $stmt->execute();

                $request_id = $db->lastInsertId();

                // Simpan nama-nama penerima
                foreach ($nama_penerima as $nama) {
                    $query = "INSERT INTO fooding_recipients (request_id, name, status) VALUES (:request_id, :name, 'Menunggu')";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':request_id', $request_id);
                    $stmt->bindParam(':name', $nama);
                    $stmt->execute();
                }

                // Kurangi stok (sementara ditahan sampai admin approve)
                // Stok akan benar-benar dikurangi setelah admin menyetujui

                // BUAT NOTIFIKASI OTOMATIS
                $message = "Pengajuan fooding " . $jumlah . " paket berhasil diajukan. Status: Menunggu";
                $notifQuery = "INSERT INTO notifications (user_id, message, status, timestamp) 
                               VALUES (:user_id, :message, 'unread', NOW())";
                $notifStmt = $db->prepare($notifQuery);
                $notifStmt->bindParam(':user_id', $user_id);
                $notifStmt->bindParam(':message', $message);
                $notifStmt->execute();

                // Commit transaction
                $db->commit();

                // Set pesan sukses dan redirect ke ajukan.php
                setNotification('Pengajuan fooding berhasil dikirim! Menunggu persetujuan admin.', 'success');
                header("Location: ajukan.php");
                exit();
            } catch (Exception $e) {
                // Rollback jika ada error
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                setNotification('Gagal mengajukan fooding: ' . $e->getMessage(), 'error');
                header("Location: ajukan.php");
                exit();
            }
        }
    }
}

// Jika ada notifikasi dari redirect, tampilkan
$error = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
$success = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';

// Hapus notifikasi dari session setelah ditampilkan
unset($_SESSION['error_message']);
unset($_SESSION['success_message']);
?>

<?php include '../components/header.php'; ?>
<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card smooth-hover">
            <div class="card-header bg-white">
                <h2 class="card-title mb-0"><i class="fas fa-utensils me-2"></i>Ajukan Fooding</h2>
            </div>
            <div class="card-body">
                <!-- Notifikasi -->
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <!-- Info Stok -->
                <div class="alert alert-info">
                    <h5><i class="fas fa-info-circle me-2"></i>Informasi Stok</h5>
                    <p>1 paket fooding = 1 Indomie + 1 Kopi</p>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between">
                                <span>Stok Indomie:</span>
                                <strong><?php echo $stok_indomie; ?> pcs</strong>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between">
                                <span>Stok Kopi:</span>
                                <strong><?php echo $stok_kopi; ?> pcs</strong>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 p-3 bg-light rounded">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold">Maksimal paket yang dapat diajukan:</span>
                            <span class="badge bg-primary fs-6"><?php echo $max_paket; ?> paket</span>
                        </div>
                    </div>
                </div>

                <!-- Form Pengajuan -->
                <form method="POST" action="" id="foodingForm">
                    <div class="mb-4">
                        <label for="jumlah" class="form-label fs-5">Jumlah Paket Fooding</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text"><i class="fas fa-box-open"></i></span>
                            <input type="number" class="form-control" id="jumlah" name="jumlah"
                                min="1" max="<?php echo $max_paket; ?>"
                                required value="<?php echo isset($_POST['jumlah']) ? $_POST['jumlah'] : 1; ?>">
                            <span class="input-group-text">paket</span>
                        </div>
                        <div class="form-text">Masukkan jumlah paket yang ingin diajukan (maksimal <?php echo $max_paket; ?> paket)</div>
                    </div>

                    <!-- Daftar Nama Penerima -->
                    <div class="mb-4" id="namaPenerimaContainer" style="display: none;">
                        <label class="form-label fs-5">Daftar Nama Penerima</label>
                        <p class="text-muted">Masukkan nama penerima untuk setiap paket fooding:</p>

                        <div id="namaPenerimaList">
                            <!-- Input nama penerima akan ditambahkan di sini oleh JavaScript -->
                        </div>

                        <div class="mt-2 text-danger" id="namaError" style="display: none;">
                            Semua nama penerima harus diisi
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" name="ajukan_fooding" class="btn btn-primary btn-lg py-3" id="submitBtn" disabled>
                            <i class="fas fa-paper-plane me-2"></i>Ajukan Fooding
                        </button>
                    </div>
                </form>

                <!-- Detail Paket -->
                <div class="mt-5">
                    <h4 class="mb-3">Detail Paket Fooding</h4>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <i class="fas fa-utensils fa-3x text-primary mb-3"></i>
                                    <h5>Indomie</h5>
                                    <p class="mb-0">1 bungkus per paket</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <i class="fas fa-coffee fa-3x text-secondary mb-3"></i>
                                    <h5>Kopi</h5>
                                    <p class="mb-0">1 sachet per paket</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const jumlahInput = document.getElementById('jumlah');
        const namaPenerimaContainer = document.getElementById('namaPenerimaContainer');
        const namaPenerimaList = document.getElementById('namaPenerimaList');
        const namaError = document.getElementById('namaError');
        const submitBtn = document.getElementById('submitBtn');
        const form = document.getElementById('foodingForm');

        // Fungsi untuk membuat input nama penerima
        function createNamaInputs(jumlah) {
            namaPenerimaList.innerHTML = '';

            for (let i = 1; i <= jumlah; i++) {
                const div = document.createElement('div');
                div.className = 'mb-3';

                const label = document.createElement('label');
                label.className = 'form-label';
                label.textContent = 'Nama Penerima ' + i;

                const inputGroup = document.createElement('div');
                inputGroup.className = 'input-group';

                const span = document.createElement('span');
                span.className = 'input-group-text';
                span.innerHTML = '<i class="fas fa-user"></i>';

                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'form-control nama-penerima';
                input.name = 'nama_penerima[]';
                input.required = true;
                input.placeholder = 'Masukkan nama penerima';

                // Jika ada nilai sebelumnya, isi kembali
                const previousValue = getPreviousValue(i);
                if (previousValue) {
                    input.value = previousValue;
                }

                input.addEventListener('input', validateNamaInputs);

                inputGroup.appendChild(span);
                inputGroup.appendChild(input);

                div.appendChild(label);
                div.appendChild(inputGroup);

                namaPenerimaList.appendChild(div);
            }
        }

        // Fungsi untuk mendapatkan nilai sebelumnya dari form
        function getPreviousValue(index) {
            const previousNama = <?php echo isset($_POST['nama_penerima']) ? json_encode($_POST['nama_penerima']) : '[]'; ?>;
            if (previousNama.length >= index) {
                return previousNama[index - 1];
            }
            return '';
        }

        // Fungsi untuk validasi input nama
        function validateNamaInputs() {
            const namaInputs = document.querySelectorAll('.nama-penerima');
            let allFilled = true;

            namaInputs.forEach(input => {
                if (input.value.trim() === '') {
                    allFilled = false;
                }
            });

            if (allFilled) {
                namaError.style.display = 'none';
                submitBtn.disabled = false;
            } else {
                namaError.style.display = 'block';
                submitBtn.disabled = true;
            }

            return allFilled;
        }

        // Tampilkan input nama saat jumlah diubah
        jumlahInput.addEventListener('change', function() {
            const jumlah = parseInt(this.value);

            if (jumlah > 0) {
                namaPenerimaContainer.style.display = 'block';
                createNamaInputs(jumlah);
                validateNamaInputs();
            } else {
                namaPenerimaContainer.style.display = 'none';
                submitBtn.disabled = true;
            }
        });

        // Validasi sebelum submit
        form.addEventListener('submit', function(e) {
            if (!validateNamaInputs()) {
                e.preventDefault();
                namaError.style.display = 'block';
                namaError.textContent = 'Semua nama penerima harus diisi';
            }
        });

        // Trigger change event untuk inisialisasi
        if (jumlahInput.value > 0) {
            jumlahInput.dispatchEvent(new Event('change'));
        }

        // Validasi real-time untuk jumlah maksimal
        jumlahInput.addEventListener('input', function() {
            const max = <?php echo $max_paket; ?>;
            const value = parseInt(this.value);

            if (value > max) {
                this.setCustomValidity('Maksimal ' + max + ' paket');
            } else {
                this.setCustomValidity('');
            }
        });
    });
</script>

<?php include '../components/footer.php'; ?>