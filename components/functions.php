<?php
// Fungsi untuk memeriksa stok
function checkStock($item_name, $jumlah) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT quantity FROM stock WHERE item_name = :item_name");
    $stmt->bindParam(':item_name', $item_name);
    $stmt->execute();
    $stock = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $stock['quantity'] >= $jumlah;
}

// Fungsi untuk mengurangi stok setelah pengajuan
function updateStock($item_name, $jumlah) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE stock SET quantity = quantity - :jumlah WHERE item_name = :item_name");
    $stmt->bindParam(':item_name', $item_name);
    $stmt->bindParam(':jumlah', $jumlah);
    $stmt->execute();
}
?>
