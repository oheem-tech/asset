<?php
// config.php
$db_file = __DIR__ . '/db/inventaris.sqlite';
$blank_file = __DIR__ . '/db/inventaris_blank.sqlite';

if (!file_exists($db_file) && file_exists($blank_file)) {
    copy($blank_file, $db_file);
}

try {
    $pdo = new PDO("sqlite:" . $db_file);
    // Set error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Enable foreign keys
    $pdo->exec("PRAGMA foreign_keys = ON;");
} catch(PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

// Auto-create tables for Pengaturan, Pejabat, & TTD
$pdo->exec("CREATE TABLE IF NOT EXISTS pengaturan (
    kunci TEXT PRIMARY KEY,
    nilai TEXT
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS ref_pejabat (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nama TEXT,
    nip TEXT
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS pengaturan_ttd (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    jenis_laporan TEXT,
    urutan INTEGER,
    jabatan TEXT,
    pejabat_id INTEGER,
    is_dinamis INTEGER DEFAULT 0 -- 1 jika ngikut transaksi (misal: Penerima)
)");

// Cek default pengaturan
$cek = $pdo->query("SELECT COUNT(*) FROM pengaturan")->fetchColumn();
if ($cek == 0) {
    $pdo->exec("INSERT INTO pengaturan (kunci, nilai) VALUES ('kop_1', 'PEMERINTAH PROVINSI')");
    $pdo->exec("INSERT INTO pengaturan (kunci, nilai) VALUES ('kop_2', 'DINAS PENDIDIKAN')");
    $pdo->exec("INSERT INTO pengaturan (kunci, nilai) VALUES ('kop_3', 'NAMA SEKOLAH')");
    $pdo->exec("INSERT INTO pengaturan (kunci, nilai) VALUES ('kop_4', 'Alamat Sekolah, Telp, Website')");
    $pdo->exec("INSERT INTO pengaturan (kunci, nilai) VALUES ('logo_kiri', '')");
}

// Function to calculate current stock for an item
function get_stok_akhir($pdo, $barang_id, $stok_awal) {
    $stmt = $pdo->prepare("SELECT 
        SUM(CASE WHEN jenis = 'Masuk' THEN jumlah ELSE 0 END) as total_masuk,
        SUM(CASE WHEN jenis = 'Keluar' THEN jumlah ELSE 0 END) as total_keluar,
        SUM(CASE WHEN jenis = 'Opname' THEN jumlah ELSE 0 END) as total_opname_diff
        FROM transaksi WHERE barang_id = ?");
    $stmt->execute([$barang_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $masuk = isset($row['total_masuk']) ? $row['total_masuk'] : 0;
    $keluar = isset($row['total_keluar']) ? $row['total_keluar'] : 0;
    $opname = isset($row['total_opname_diff']) ? $row['total_opname_diff'] : 0;
    
    return $stok_awal + $masuk - $keluar + $opname;
}
?>
