<?php
// test_koneksi.php - Test koneksi database
echo "<h2>Test Koneksi Database</h2>";

require_once 'components/connect.php';

$database = new Database();
try {
    $db = $database->getConnection();
    echo "<p style='color: green;'>✅ Koneksi database BERHASIL</p>";
    
    // Test query sederhana
    $stmt = $db->query("SELECT VERSION() as version");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>MySQL Version: " . $result['version'] . "</p>";
    
    // Test INSERT
    $test_data = "Test data " . date('Y-m-d H:i:s');
    $query = "INSERT INTO test_table (data) VALUES (?)";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute([$test_data])) {
        echo "<p style='color: green;'>✅ Test INSERT BERHASIL</p>";
    } else {
        echo "<p style='color: red;'>❌ Test INSERT GAGAL</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Koneksi database GAGAL: " . $e->getMessage() . "</p>";
    echo "<p>Pastikan:</p>";
    echo "<ul>";
    echo "<li>Database 'efooding_db' exists</li>";
    echo "<li>User memiliki privilege yang cukup</li>";
    echo "<li>Password database benar</li>";
    echo "</ul>";
}

// Buat tabel test jika belum ada
echo "<h3>Buat Tabel Test</h3>";
try {
    $db = $database->getConnection();
    $query = "CREATE TABLE IF NOT EXISTS test_table (
        id INT AUTO_INCREMENT PRIMARY KEY,
        data VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $db->exec($query);
    echo "<p style='color: green;'>✅ Tabel test siap</p>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Gagal buat tabel: " . $e->getMessage() . "</p>";
}
?>