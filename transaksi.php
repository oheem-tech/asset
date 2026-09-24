<?php
require_once 'config.php';

$error_msg = '';
$success_msg = '';

// Add Transaksi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_transaksi') {
    $tanggal = $_POST['tanggal'];
    $jenis = $_POST['jenis'];
    $keterangan = $_POST['keterangan'];
    $bukti = $_POST['bukti'];
    
    $barang_ids = isset($_POST['barang_id']) ? $_POST['barang_id'] : [];
    $jumlahs = isset($_POST['jumlah']) ? $_POST['jumlah'] : [];
    
    $success_count = 0;
    $can_insert = true;
    
    // Validasi stok semua barang terlebih dahulu (sekarang STOK MINUS diizinkan, sehingga validasi dilewati)
    foreach ($barang_ids as $idx => $barang_id) {
        $jumlah = isset($jumlahs[$idx]) ? (int)$jumlahs[$idx] : 0;
        if (empty($barang_id) || $jumlah <= 0) continue;
        // Tidak ada error, lanjut
    }
    
    if ($can_insert) {
        foreach ($barang_ids as $idx => $barang_id) {
            $jumlah = isset($jumlahs[$idx]) ? (int)$jumlahs[$idx] : 0;
            if (empty($barang_id) || $jumlah <= 0) continue;
            
            $stmt = $pdo->prepare("INSERT INTO transaksi (tanggal, jenis, barang_id, jumlah, keterangan, bukti) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$tanggal, $jenis, $barang_id, $jumlah, $keterangan, $bukti]);
            $success_count++;
        }
        if ($success_count > 0) {
            $success_msg = "Berhasil mencatat $success_count barang ke dalam transaksi!";
        } else {
            $error_msg = "Tidak ada barang yang dipilih.";
        }
    }
}

// Edit Transaksi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit_transaksi') {
    $id = $_POST['id'];
    $barang_id = $_POST['barang_id'];
    $tanggal = $_POST['tanggal'];
    $jenis = $_POST['jenis'];
    $jumlah = (int)$_POST['jumlah'];
    $keterangan = $_POST['keterangan'];
    $bukti = $_POST['bukti'];
    
    $t_stmt = $pdo->prepare("SELECT * FROM transaksi WHERE id = ?");
    $t_stmt->execute([$id]);
    $old_trx = $t_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($old_trx) {
        $b_stmt = $pdo->prepare("SELECT stok_awal FROM barang WHERE id = ?");
        $b_stmt->execute([$barang_id]);
        $b = $b_stmt->fetch(PDO::FETCH_ASSOC);
        
        $stok_sekarang = get_stok_akhir($pdo, $barang_id, $b['stok_awal']);
        
        // Reverse old transaction effect to get hypothetical stock before this edit
        if ($old_trx['jenis'] == 'Keluar' && $old_trx['barang_id'] == $barang_id) {
            $stok_sekarang += $old_trx['jumlah'];
        } else if ($old_trx['jenis'] == 'Masuk' && $old_trx['barang_id'] == $barang_id) {
            $stok_sekarang -= $old_trx['jumlah'];
        } else if ($old_trx['barang_id'] != $barang_id) {
            // If changing item entirely, the selected item's stock is just its current stock
            $stok_sekarang = get_stok_akhir($pdo, $barang_id, $b['stok_awal']); 
        }
        
        $stok_baru = $stok_sekarang;
        if ($jenis == 'Keluar') {
            $stok_baru -= $jumlah;
        } else if ($jenis == 'Masuk') {
            $stok_baru += $jumlah;
        }
        
        // STOK MINUS diizinkan, eksekusi query langsung
        $stmt = $pdo->prepare("UPDATE transaksi SET tanggal=?, jenis=?, barang_id=?, jumlah=?, keterangan=?, bukti=? WHERE id=?");
        $stmt->execute([$tanggal, $jenis, $barang_id, $jumlah, $keterangan, $bukti, $id]);
        $success_msg = "Transaksi berhasil diperbarui!";
    }
}

// Delete Transaksi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete') {
    $stmt = $pdo->prepare("DELETE FROM transaksi WHERE id = ?");
    $stmt->execute([$_POST['id']]);
    header("Location: transaksi.php");
    exit;
}

include 'layout_header.php';

// Get items for dropdown
$barang_list = $pdo->query("SELECT id, kode_barang, nama_barang FROM barang ORDER BY nama_barang ASC")->fetchAll(PDO::FETCH_ASSOC);

// Get recent transactions with filter
$search = isset($_GET['q']) ? $_GET['q'] : '';
$filter_jenis = isset($_GET['filter_jenis']) ? $_GET['filter_jenis'] : '';

$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(t.bukti LIKE ? OR t.keterangan LIKE ? OR b.nama_barang LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($filter_jenis !== '') {
    $where[] = "t.jenis = ?";
    $params[] = $filter_jenis;
}

$where_sql = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";

$stmt = $pdo->prepare("
    SELECT t.*, b.nama_barang, b.kode_barang 
    FROM transaksi t 
    JOIN barang b ON t.barang_id = b.id 
    $where_sql
    ORDER BY t.tanggal DESC, t.id DESC
");
$stmt->execute($params);
$transaksi_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2">Transaksi Mutasi Barang</h1>
    <div>
        <a href="import_transaksi.php" class="btn btn-success me-2"><i class="bi bi-file-earmark-spreadsheet"></i> Impor Riwayat Transaksi</a>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTrxModal"><i class="bi bi-plus-circle"></i> Catat Transaksi</button>
    </div>
</div>

<?php if($error_msg): ?>
<div class="alert alert-danger"><?= htmlspecialchars($error_msg) ?></div>
<?php endif; ?>
<?php if($success_msg): ?>
<div class="alert alert-success"><?= htmlspecialchars($success_msg) ?></div>
<?php endif; ?>

<?php
// Hitung Statistik Transaksi
$stat_total = count($transaksi_list);
$stat_masuk = 0;
$stat_keluar = 0;

foreach ($transaksi_list as $t) {
    if ($t['jenis'] == 'Masuk') {
        $stat_masuk++;
    } elseif ($t['jenis'] == 'Keluar') {
        $stat_keluar++;
    }
}
?>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card text-white bg-primary shadow-sm h-100">
            <div class="card-body">
                <h6 class="card-title text-uppercase text-white-50 fw-bold">Total Transaksi</h6>
                <h3 class="mb-0"><?= number_format($stat_total, 0, ',', '.') ?> <small class="fs-6">Catatan</small></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-success shadow-sm h-100">
            <div class="card-body">
                <h6 class="card-title text-uppercase text-white-50 fw-bold">Frekuensi Barang Masuk</h6>
                <h3 class="mb-0"><?= number_format($stat_masuk, 0, ',', '.') ?> <small class="fs-6">Kali Penerimaan</small></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-warning text-dark shadow-sm h-100">
            <div class="card-body">
                <h6 class="card-title text-uppercase text-dark-50 fw-bold" style="opacity: 0.7;">Frekuensi Barang Keluar</h6>
                <h3 class="mb-0"><?= number_format($stat_keluar, 0, ',', '.') ?> <small class="fs-6">Kali Penyaluran</small></h3>
            </div>
        </div>
    </div>
</div>

<!-- Search Form -->
<form method="get" class="mb-3 d-flex gap-2 w-75">
    <input type="text" name="q" class="form-control" placeholder="Cari bukti, nama barang, atau keterangan..." value="<?= htmlspecialchars($search) ?>">
    <select name="filter_jenis" class="form-select" style="max-width: 200px;">
        <option value="">Semua Mutasi</option>
        <option value="Masuk" <?= $filter_jenis == 'Masuk' ? 'selected' : '' ?>>Hanya Masuk</option>
        <option value="Keluar" <?= $filter_jenis == 'Keluar' ? 'selected' : '' ?>>Hanya Keluar</option>
    </select>
    <button type="submit" class="btn btn-outline-secondary">Filter</button>
    <?php if($search || $filter_jenis): ?>
    <a href="transaksi.php" class="btn btn-outline-danger">Reset</a>
    <?php endif; ?>
</form>

<div class="table-responsive">
    <table class="table table-striped table-bordered table-sm align-middle">
        <thead class="table-dark">
            <tr>
                <th>Tanggal</th>
                <th>Jenis Mutasi</th>
                <th>No Bukti / Referensi</th>
                <th>Barang</th>
                <th>Jumlah</th>
                <th>Keterangan</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($transaksi_list as $t): ?>
            <tr>
                <td><?= htmlspecialchars($t['tanggal']) ?></td>
                <td>
                    <?php if($t['jenis'] == 'Masuk'): ?>
                        <span class="badge bg-success">Masuk / Penerimaan</span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark">Keluar / Penyaluran</span>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($t['bukti']) ?></td>
                <td><?= htmlspecialchars($t['kode_barang'] . ' - ' . $t['nama_barang']) ?></td>
                <td><?= htmlspecialchars($t['jumlah']) ?></td>
                <td><?= htmlspecialchars($t['keterangan']) ?></td>
                <td>
                    <div class="d-flex flex-nowrap gap-1">
                        <button type="button" class="btn btn-warning btn-sm" 
                            data-id="<?= $t['id'] ?>"
                            data-tanggal="<?= htmlspecialchars($t['tanggal']) ?>"
                            data-jenis="<?= htmlspecialchars($t['jenis']) ?>"
                            data-bukti="<?= htmlspecialchars($t['bukti']) ?>"
                            data-barang="<?= $t['barang_id'] ?>"
                            data-jumlah="<?= $t['jumlah'] ?>"
                            data-keterangan="<?= htmlspecialchars($t['keterangan']) ?>"
                            onclick="editTrx(this)" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </button>
                        
                        <form method="post" onsubmit="return confirm('Hapus transaksi ini? Stok akan dikembalikan ke posisi sebelum transaksi ini.');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $t['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm" title="Hapus"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($transaksi_list)): ?>
            <tr><td colspan="7" class="text-center">Belum ada data transaksi atau pencarian tidak ditemukan.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
$ref_penerima = $pdo->query("SELECT nama FROM ref_penerima ORDER BY nama ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<datalist id="penerima_list">
    <?php foreach($ref_penerima as $rp): ?>
    <option value="<?= htmlspecialchars($rp['nama']) ?>">
    <?php endforeach; ?>
</datalist>

<!-- Modal Transaksi -->
<div class="modal fade" id="addTrxModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="post">
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title">Input Transaksi (Masuk/Keluar) Multi-Barang</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="action" value="add_transaksi">
            
            <div class="row bg-light p-3 border mb-3">
                <div class="col-md-6 mb-2">
                    <label class="form-label fw-bold">Tanggal Transaksi</label>
                    <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-6 mb-2">
                    <label class="form-label fw-bold">Jenis Mutasi</label>
                    <select name="jenis" class="form-select" required>
                        <option value="Masuk">Masuk (Penerimaan)</option>
                        <option value="Keluar">Keluar (Pengeluaran / Distribusi)</option>
                    </select>
                </div>
                <div class="col-md-6 mb-2">
                    <label class="form-label fw-bold">No Bukti (BAST / Nota)</label>
                    <input type="text" name="bukti" class="form-control" required>
                </div>
                <div class="col-md-6 mb-2">
                    <label class="form-label fw-bold">Keterangan / Penerima / Sumber</label>
                    <input type="text" name="keterangan" list="penerima_list" class="form-control" placeholder="Pilih atau ketik baru..." required>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-bold mb-0">Daftar Barang</h6>
                <button type="button" class="btn btn-sm btn-success" onclick="addBarangRow()"><i class="bi bi-plus-circle"></i> Tambah Baris Barang</button>
            </div>
            
            <div id="barangContainer">
                <div class="row align-items-end mb-2 barang-row">
                    <div class="col-md-8">
                        <label class="form-label small">Nama Barang</label>
                        <select name="barang_id[]" class="form-select ts-select" required>
                            <option value="">-- Pilih Barang --</option>
                            <?php foreach ($barang_list as $b): ?>
                            <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['kode_barang'] . ' - ' . $b['nama_barang']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Jumlah</label>
                        <input type="number" name="jumlah[]" class="form-control" min="1" required>
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-danger" onclick="removeBarangRow(this)" disabled><i class="bi bi-trash"></i></button>
                    </div>
                </div>
            </div>
            
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary">Simpan Transaksi</button>
          </div>
      </form>
    </div>
  </div>
</div>

<script>
let editTs = null;

function initTS(el) {
    if (el.tomselect) {
        el.tomselect.destroy();
    }
    new TomSelect(el, {
        create: false,
        sortField: {
            field: "text",
            direction: "asc"
        }
    });
}

document.addEventListener("DOMContentLoaded", function() {
    // Init on first load
    document.querySelectorAll('.ts-select').forEach(function(el) {
        initTS(el);
    });
});

function addBarangRow() {
    let container = document.getElementById('barangContainer');
    let firstRow = container.querySelector('.barang-row');
    
    // TomSelect changes the DOM significantly (adds wrapper, hides original select).
    // So we destroy the first select temporarily, clone the clean DOM, then re-init both.
    let firstSelect = firstRow.querySelector('select');
    if (firstSelect.tomselect) {
        firstSelect.tomselect.destroy();
    }
    
    let newRow = firstRow.cloneNode(true);
    
    // Clear inputs in new row
    newRow.querySelector('select').value = '';
    newRow.querySelector('input[type="number"]').value = '';
    
    // Enable delete button
    newRow.querySelector('.btn-danger').disabled = false;
    
    container.appendChild(newRow);
    
    // Re-init both
    initTS(firstSelect);
    initTS(newRow.querySelector('select'));
}

function removeBarangRow(btn) {
    let row = btn.closest('.barang-row');
    if (document.querySelectorAll('.barang-row').length > 1) {
        row.remove();
    }
}
</script>

<!-- Modal Edit Transaksi -->
<div class="modal fade" id="editTrxModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post">
          <div class="modal-header">
            <h5 class="modal-title">Edit Transaksi</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="action" value="edit_transaksi">
            <input type="hidden" name="id" id="edit_id">
            <div class="mb-3">
                <label>Tanggal Transaksi</label>
                <input type="date" name="tanggal" id="edit_tanggal" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Jenis Mutasi</label>
                <select name="jenis" id="edit_jenis" class="form-select" required>
                    <option value="Masuk">Masuk (Penerimaan)</option>
                    <option value="Keluar">Keluar (Pengeluaran / Distribusi)</option>
                </select>
            </div>
            <div class="mb-3">
                <label>No Bukti (BAST / Nota)</label>
                <input type="text" name="bukti" id="edit_bukti" class="form-control">
            </div>
            <div class="mb-3">
                <label>Barang</label>
                <select name="barang_id" id="edit_barang_id" class="form-select ts-select" required>
                    <option value="">-- Pilih Barang --</option>
                    <?php foreach ($barang_list as $b): ?>
                    <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['kode_barang'] . ' - ' . $b['nama_barang']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label>Jumlah</label>
                <input type="number" name="jumlah" id="edit_jumlah" class="form-control" min="1" required>
            </div>
            <div class="mb-3">
                <label>Keterangan / Penerima</label>
                <input type="text" name="keterangan" id="edit_keterangan" list="penerima_list" class="form-control" placeholder="Pilih atau ketik baru...">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-success">Update Transaksi</button>
          </div>
      </form>
    </div>
  </div>
</div>

<script>
function editTrx(btn) {
    document.getElementById('edit_id').value = btn.getAttribute('data-id');
    document.getElementById('edit_tanggal').value = btn.getAttribute('data-tanggal');
    document.getElementById('edit_jenis').value = btn.getAttribute('data-jenis');
    document.getElementById('edit_bukti').value = btn.getAttribute('data-bukti');
    
    let barangId = btn.getAttribute('data-barang');
    let selectEl = document.getElementById('edit_barang_id');
    if (selectEl.tomselect) {
        selectEl.tomselect.setValue(barangId);
    } else {
        selectEl.value = barangId;
    }
    
    document.getElementById('edit_jumlah').value = btn.getAttribute('data-jumlah');
    document.getElementById('edit_keterangan').value = btn.getAttribute('data-keterangan');
    
    var editModal = new bootstrap.Modal(document.getElementById('editTrxModal'));
    editModal.show();
}
</script>

<?php include 'layout_footer.php'; ?>
