<?php
require_once 'config.php';

try {
    // Cari semua data yang punya tanda kutip (') di kode atau uraian
    $stmt = $pdo->query("SELECT kode, uraian FROM ref_kode_barang WHERE kode LIKE '%''%' OR uraian LIKE '%''%'");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $count = 0;
    
    // Nonaktifkan foreign key sebentar jika perlu, tapi kita pakai transaksi
    $pdo->beginTransaction();

    foreach ($rows as $row) {
        $old_kode = $row['kode'];
        $old_uraian = $row['uraian'];
        
        // Hapus tanda kutip (')
        $new_kode = str_replace("'", "", $old_kode);
        $new_uraian = str_replace("'", "", $old_uraian);
        
        // Jika kode berubah, kita perlu hati-hati karena kode adalah PRIMARY KEY.
        // SQLite tidak mengizinkan UPDATE primary key jika kita tidak punya opsi khusus, tapi sebenarnya UPDATE PK diizinkan.
        if ($old_kode !== $new_kode) {
            // Insert the new one
            $ins = $pdo->prepare("INSERT OR IGNORE INTO ref_kode_barang (kode, uraian) VALUES (?, ?)");
            $ins->execute([$new_kode, $new_uraian]);
            
            // Cascade update ke barang
            $upd = $pdo->prepare("UPDATE barang SET kode_barang = ? WHERE kode_barang = ?");
            $upd->execute([$new_kode, $old_kode]);
            
            // Hapus yang lama
            $del = $pdo->prepare("DELETE FROM ref_kode_barang WHERE kode = ?");
            $del->execute([$old_kode]);
        } else {
            // Hanya uraian yang berubah
            $upd = $pdo->prepare("UPDATE ref_kode_barang SET uraian = ? WHERE kode = ?");
            $upd->execute([$new_uraian, $old_kode]);
        }
        $count++;
    }
    
    $pdo->commit();
    echo "Berhasil membuang tanda kutip dari $count data.";

} catch (PDOException $e) {
    $pdo->rollBack();
    echo "Error: " . $e->getMessage();
}
?>
