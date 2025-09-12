<?php
// debug_test.php
echo "<h2>🐛 DEBUGGING E-FOODING</h2>";
echo "<p>Mari kita cari tahu dimana masalahnya!</p>";

// 1. Test Session
echo "<h3>1. Test Session</h3>";
session_start();
$_SESSION['debug_user'] = 'test_user';
echo "Session ID: " . session_id() . "<br>";
echo "Session data: ";
print_r($_SESSION);
echo "<hr>";

// 2. Test Koneksi Database
echo "<h3>2. Test Koneksi Database</h3>";
try {
    $db = new PDO('mysql:host=localhost;dbname=efooding_db', 'root', 'admin');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Koneksi database BERHASIL<br>";
    
    // Test query sederhana
    $stmt = $db->query("SELECT COUNT(*) as total FROM users");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total users: " . $result['total'] . "<br>";
    
} catch (PDOException $e) {
    echo "❌ Koneksi database GAGAL: " . $e->getMessage() . "<br>";
}
echo "<hr>";

// 3. Test POST Data
echo "<h3>3. Test POST Data</h3>";
echo "Method request: " . $_SERVER['REQUEST_METHOD'] . "<br>";
echo "POST data: ";
print_r($_POST);
echo "<br><br>";

// Form test
echo '
<form method="POST">
    <input type="number" name="test_jumlah" value="1">
    <button type="submit" name="test_button">Test POST</button>
</form>
';
echo "<hr>";

// 4. Test Insert Data
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['test_button'])) {
    echo "<h3>4. Test Insert Data</h3>";
    try {
        $db = new PDO('mysql:host=localhost;dbname=efooding_db', 'root', 'admin');
        
        // Test insert
        $stmt = $db->prepare("INSERT INTO fooding_requests (user_id, jumlah, status) VALUES (1, ?, 'Test')");
        $stmt->execute([$_POST['test_jumlah']]);
        
        echo "✅ Insert data BERHASIL! ID: " . $db->lastInsertId() . "<br>";
        
        // Test update stock
        $stmt = $db->prepare("UPDATE stock SET quantity = quantity - ? WHERE item_name = 'Indomie'");
        $stmt->execute([$_POST['test_jumlah']]);
        echo "✅ Update stok Indomie BERHASIL<br>";
        
        $stmt = $db->prepare("UPDATE stock SET quantity = quantity - ? WHERE item_name = 'Kopi'");
        $stmt->execute([$_POST['test_jumlah']]);
        echo "✅ Update stok Kopi BERHASIL<br>";
        
    } catch (PDOException $e) {
        echo "❌ Error database: " . $e->getMessage() . "<br>";
    }
    echo "<hr>";
}

// 5. Test File Includes
echo "<h3>5. Test File Includes</h3>";
$files_to_test = [
    'components/connect.php',
    'components/functions.php',
    'components/header.php',
    'components/footer.php'
];

foreach ($files_to_test as $file) {
    if (file_exists($file)) {
        echo "✅ $file - ADA<br>";
    } else {
        echo "❌ $file - TIDAK ADA<br>";
    }
}
echo "<hr>";

// 6. Test Simple Form
echo "<h3>6. Test Simple Form Submission</h3>";
echo '
<form method="POST" action="?simple_test=1">
    <input type="number" name="jumlah" value="1" min="1">
    <button type="submit" name="ajukan_fooding">Test Ajukan Fooding</button>
</form>
';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajukan_fooding'])) {
    echo "<div style='background: #d4edda; padding: 10px; margin: 10px 0;'>";
    echo "✅ FORM BERHASIL DISUBMIT!<br>";
    echo "Jumlah: " . ($_POST['jumlah'] ?? 'NULL');
    echo "</div>";
}
?>

<style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    .success { background: #d4edda; padding: 10px; margin: 10px 0; }
    .error { background: #f8d7da; padding: 10px; margin: 10px 0; }
</style>