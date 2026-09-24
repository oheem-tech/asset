<?php
// init_db.php
require_once 'config.php';

try {
    // Create Master Barang Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS barang (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        kode_barang TEXT NOT NULL,
        nama_barang TEXT NOT NULL,
        kategori TEXT NOT NULL,
        satuan TEXT NOT NULL,
        harga_satuan REAL NOT NULL DEFAULT 0,
        stok_awal INTEGER NOT NULL DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Create Transaksi Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS transaksi (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        tanggal DATE NOT NULL,
        jenis TEXT NOT NULL, -- 'Masuk', 'Keluar', 'Opname'
        barang_id INTEGER NOT NULL,
        jumlah INTEGER NOT NULL, -- Can be negative for Opname diff, but usually positive for In/Out
        keterangan TEXT,
        bukti TEXT, -- Ref number (No BAST/No SPB)
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (barang_id) REFERENCES barang(id) ON DELETE RESTRICT
    )");

    echo "Database initialized successfully.\n";
} catch (PDOException $e) {
    echo "Error initializing database: " . $e->getMessage() . "\n";
}
?>
