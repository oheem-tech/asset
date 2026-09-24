<?php
require_once 'config.php';

$error_msg = '';
$success_msg = '';

// Handle Delete / Update Main Bukti
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'update_bukti') {
        $old_bukti = $_POST['old_bukti'];
        $new_bukti = trim($_POST['new_bukti']);
        
        if (!empty($new_bukti) && $new_bukti !== $old_bukti) {
            $pdo->beginTransaction();
            try {
                // Update transaksi
                $stmt = $pdo->prepare("UPDATE transaksi SET bukti = ? WHERE bukti = ?");
                $stmt->execute([$new_bukti, $old_bukti]);
                
                // Update dokumen_pengeluaran
                $stmt2 = $pdo->prepare("UPDATE dokumen_pengeluaran SET bukti_transaksi = ? WHERE bukti_transaksi = ?");
                $stmt2->execute([$new_bukti, $old_bukti]);
                
                // Update dokumen_penerimaan
                $stmt3 = $pdo->prepare("UPDATE dokumen_penerimaan SET bukti_transaksi = ? WHERE bukti_transaksi = ?");
                $stmt3->execute([$new_bukti, $old_bukti]);
                
                $pdo->commit();
                $success_msg = "Nomor Surat/Bukti Utama berhasil diubah dari '$old_bukti' menjadi '$new_bukti'. Semua barang yang terkait telah diperbarui.";
            } catch (Exception $e) {
                $pdo->rollBack();
                $error_msg = "Gagal merubah nomor bukti: " . $e->getMessage();
            }
        }
    }
}

include 'layout_header.php';

$search = isset($_GET['q']) ? $_GET['q'] : '';
$where = "WHERE bukti != ''";
$params = [];
if ($search !== '') {
    $where .= " AND (bukti LIKE ? OR keterangan LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Get grouped transactions
$stmt = $pdo->prepare("
    SELECT tanggal, jenis, bukti, MAX(keterangan) as keterangan, COUNT(id) as total_item 
    FROM transaksi 
    $where 
    GROUP BY bukti, tanggal, jenis 
    ORDER BY tanggal DESC
");
$stmt->execute($params);
$docs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2">Manajemen Dokumen Surat</h1>
</div>

<?php if($error_msg): ?>
<div class="alert alert-danger"><?= htmlspecialchars($error_msg) ?></div>
<?php endif; ?>
<?php if($success_msg): ?>
<div class="alert alert-success"><?= htmlspecialchars($success_msg) ?></div>
<?php endif; ?>

<div class="alert alert-info">
    Menu ini menampilkan seluruh riwayat transaksi yang dikelompokkan berdasarkan <strong>Nomor Bukti Utama / Surat</strong>. Anda dapat mengedit Nomor Bukti Utama di sini, dan otomatis akan mengubah nomor surat pada seluruh barang yang ada di dalamnya secara bersamaan (Batch Edit).
</div>

<!-- Search Form -->
<form method="get" class="mb-3 d-flex gap-2 w-50">
    <input type="text" name="q" class="form-control" placeholder="Cari nomor bukti atau keterangan..." value="<?= htmlspecialchars($search) ?>">
    <button type="submit" class="btn btn-outline-secondary">Cari</button>
    <?php if($search): ?>
    <a href="dokumen.php" class="btn btn-outline-danger">Reset</a>
    <?php endif; ?>
</form>

<div class="table-responsive">
    <table class="table table-bordered table-hover align-middle">
        <thead class="table-dark text-center">
            <tr>
                <th width="10%">Tanggal</th>
                <th width="10%">Jenis Mutasi</th>
                <th width="25%">Nomor Bukti Utama (Ref)</th>
                <th width="25%">Keterangan / Sumber / Tujuan</th>
                <th width="10%">Total Item</th>
                <th width="20%">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($docs as $d): ?>
            <tr>
                <td class="text-center"><?= htmlspecialchars($d['tanggal']) ?></td>
                <td class="text-center fw-bold <?= $d['jenis'] == 'Masuk' ? 'text-success' : 'text-danger' ?>"><?= htmlspecialchars($d['jenis']) ?></td>
                <td class="fw-bold"><?= htmlspecialchars($d['bukti']) ?></td>
                <td><?= htmlspecialchars($d['keterangan']) ?></td>
                <td class="text-center"><span class="badge bg-secondary"><?= $d['total_item'] ?> Barang</span></td>
                <td class="text-center">
                    <button class="btn btn-sm btn-primary" onclick="editBukti('<?= htmlspecialchars($d['bukti']) ?>')"><i class="bi bi-pencil-square"></i> Edit No Bukti</button>
                    <!-- Tombol Detail Dokumen (Link ke Cetak Laporan) -->
                    <?php if ($d['jenis'] == 'Keluar'): ?>
                    <a href="laporan.php?jenis=dokumen_pengeluaran&trx_bukti=<?= urlencode($d['bukti']) ?>" class="btn btn-sm btn-info text-white"><i class="bi bi-file-text"></i> Cek 4 Surat</a>
                    <?php else: ?>
                    <a href="laporan.php?jenis=bast&trx_bukti=<?= urlencode($d['bukti']) ?>" class="btn btn-sm btn-info text-white"><i class="bi bi-file-text"></i> Cek BA/BAST</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($docs)): ?>
            <tr><td colspan="6" class="text-center">Tidak ada dokumen surat yang ditemukan.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal Edit Bukti -->
<div class="modal fade" id="editBuktiModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="dokumen.php">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title">Edit Nomor Bukti Utama</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="action" value="update_bukti">
            <input type="hidden" name="old_bukti" id="input_old_bukti">
            
            <div class="mb-3">
                <label class="form-label">Nomor Bukti Lama</label>
                <input type="text" id="display_old_bukti" class="form-control" readonly disabled>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Nomor Bukti Baru</label>
                <input type="text" name="new_bukti" class="form-control" required placeholder="Masukkan nomor surat yang benar...">
                <small class="text-muted">Peringatan: Mengubah nomor ini akan otomatis merubah nomor referensi pada semua barang yang tergabung di dalam transaksi ini.</small>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function editBukti(oldBukti) {
    document.getElementById('input_old_bukti').value = oldBukti;
    document.getElementById('display_old_bukti').value = oldBukti;
    var myModal = new bootstrap.Modal(document.getElementById('editBuktiModal'));
    myModal.show();
}
</script>

<?php include 'layout_footer.php'; ?>
