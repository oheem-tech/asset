<?php
require_once 'config.php';

$error_msg = '';

// Handle Add / Edit / Delete
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'add') {
        $spesifikasi = isset($_POST['spesifikasi']) ? $_POST['spesifikasi'] : '';
        $stmt = $pdo->prepare("INSERT INTO barang (kode_barang, nama_barang, spesifikasi, kategori, satuan, harga_satuan, stok_awal) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['kode_barang'], $_POST['nama_barang'], $spesifikasi, $_POST['kategori'], $_POST['satuan'], $_POST['harga_satuan'], $_POST['stok_awal']
        ]);
        header("Location: master.php");
        exit;
    } elseif (isset($_POST['action']) && $_POST['action'] == 'edit') {
        $spesifikasi = isset($_POST['spesifikasi']) ? $_POST['spesifikasi'] : '';
        $stmt = $pdo->prepare("UPDATE barang SET kode_barang=?, nama_barang=?, spesifikasi=?, kategori=?, satuan=?, harga_satuan=?, stok_awal=? WHERE id=?");
        $stmt->execute([
            $_POST['kode_barang'], $_POST['nama_barang'], $spesifikasi, $_POST['kategori'], $_POST['satuan'], $_POST['harga_satuan'], $_POST['stok_awal'], $_POST['id']
        ]);
        header("Location: master.php");
        exit;
    } elseif (isset($_POST['action']) && $_POST['action'] == 'delete') {
        // Only delete if no transactions exist, or restrict
        try {
            $stmt = $pdo->prepare("DELETE FROM barang WHERE id = ?");
            $stmt->execute([$_POST['id']]);
        } catch (PDOException $e) {
            $error_msg = "Tidak bisa menghapus barang yang sudah memiliki transaksi.";
        }
    }
}

include 'layout_header.php';

$search = isset($_GET['q']) ? $_GET['q'] : '';
$where = "";
$params = [];
if ($search !== '') {
    $where = "WHERE nama_barang LIKE ? OR kode_barang LIKE ?";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$stmt = $pdo->prepare("SELECT * FROM barang $where ORDER BY id DESC");
$stmt->execute($params);
$barang_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2">Master Barang</h1>
    <div>
        <a href="import_master.php" class="btn btn-success me-2"><i class="bi bi-file-earmark-spreadsheet"></i> Impor Excel/Copy-Paste</a>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus-circle"></i> Tambah Barang</button>
    </div>
</div>

<?php if($error_msg): ?>
<div class="alert alert-danger"><?= htmlspecialchars($error_msg) ?></div>
<?php endif; ?>

<?php
// Hitung Statistik Master Barang
$stat_jenis = count($barang_list);
$stat_nilai = 0;
$stat_kosong = 0;

foreach ($barang_list as $b) {
    $stok_akhir = get_stok_akhir($pdo, $b['id'], $b['stok_awal']);
    $stat_nilai += ($stok_akhir * $b['harga_satuan']);
    if ($stok_akhir <= 0) $stat_kosong++;
}
?>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card text-white bg-primary shadow-sm h-100">
            <div class="card-body">
                <h6 class="card-title text-uppercase text-white-50 fw-bold">Total Jenis Barang</h6>
                <h3 class="mb-0"><?= number_format($stat_jenis, 0, ',', '.') ?> Item</h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-success shadow-sm h-100">
            <div class="card-body">
                <h6 class="card-title text-uppercase text-white-50 fw-bold">Total Nilai Aset Saat Ini</h6>
                <h3 class="mb-0">Rp <?= number_format($stat_nilai, 0, ',', '.') ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-danger shadow-sm h-100">
            <div class="card-body">
                <h6 class="card-title text-uppercase text-white-50 fw-bold">Barang Stok Kosong</h6>
                <h3 class="mb-0"><?= number_format($stat_kosong, 0, ',', '.') ?> Item</h3>
            </div>
        </div>
    </div>
</div>

<!-- Search Form -->
<form method="get" class="mb-3 d-flex gap-2 w-50">
    <input type="text" name="q" class="form-control" placeholder="Cari nama atau kode barang..." value="<?= htmlspecialchars($search) ?>">
    <button type="submit" class="btn btn-outline-secondary">Cari</button>
    <?php if($search): ?>
    <a href="master.php" class="btn btn-outline-danger">Reset</a>
    <?php endif; ?>
</form>

<div class="table-responsive">
    <table class="table table-striped table-bordered table-sm align-middle">
        <thead class="table-dark">
            <tr>
                <th>Kode</th>
                <th>Nama/Spesifikasi</th>
                <th>Kategori</th>
                <th>Satuan</th>
                <th>Harga Satuan (Rp)</th>
                <th>Stok Awal</th>
                <th>Masuk</th>
                <th>Keluar</th>
                <th>Stok Akhir</th>
                <th>Total Nilai (Rp)</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($barang_list as $b): 
                // Calculate stock details
                $s = $pdo->prepare("SELECT 
                    SUM(CASE WHEN jenis = 'Masuk' THEN jumlah ELSE 0 END) as t_masuk,
                    SUM(CASE WHEN jenis = 'Keluar' THEN jumlah ELSE 0 END) as t_keluar
                    FROM transaksi WHERE barang_id = ?");
                $s->execute([$b['id']]);
                $trx = $s->fetch(PDO::FETCH_ASSOC);
                
                $masuk = isset($trx['t_masuk']) ? $trx['t_masuk'] : 0;
                $keluar = isset($trx['t_keluar']) ? $trx['t_keluar'] : 0;
                
                $stok_akhir = get_stok_akhir($pdo, $b['id'], $b['stok_awal']);
                $total_nilai = $stok_akhir * $b['harga_satuan'];
                
                $spek = isset($b['spesifikasi']) ? $b['spesifikasi'] : '';
            ?>
            <tr>
                <td><?= htmlspecialchars($b['kode_barang']) ?></td>
                <td>
                    <strong><?= htmlspecialchars($b['nama_barang']) ?></strong>
                    <?php if($spek): ?><br><small class="text-muted"><?= htmlspecialchars($spek) ?></small><?php endif; ?>
                </td>
                <td><?= htmlspecialchars($b['kategori']) ?></td>
                <td><?= htmlspecialchars($b['satuan']) ?></td>
                <td><?= number_format($b['harga_satuan'], 0, ',', '.') ?></td>
                <td><?= htmlspecialchars($b['stok_awal']) ?></td>
                <td><?= $masuk ?></td>
                <td><?= $keluar ?></td>
                <td class="<?= ($stok_akhir < 10) ? 'text-danger fw-bold' : 'text-success fw-bold' ?>">
                    <?= $stok_akhir ?>
                </td>
                <td><?= number_format($total_nilai, 0, ',', '.') ?></td>
                <td>
                    <div class="d-flex flex-nowrap gap-1">
                        <button type="button" class="btn btn-warning btn-sm" 
                            data-id="<?= $b['id'] ?>"
                            data-kode="<?= htmlspecialchars($b['kode_barang']) ?>"
                            data-nama="<?= htmlspecialchars($b['nama_barang']) ?>"
                            data-spek="<?= htmlspecialchars($spek) ?>"
                            data-kat="<?= htmlspecialchars($b['kategori']) ?>"
                            data-satuan="<?= htmlspecialchars($b['satuan']) ?>"
                            data-harga="<?= htmlspecialchars($b['harga_satuan']) ?>"
                            data-stok="<?= htmlspecialchars($b['stok_awal']) ?>"
                            onclick="editBarang(this)" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </button>
                        
                        <form method="post" onsubmit="return confirm('Hapus barang ini?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $b['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm" title="Hapus"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($barang_list)): ?>
            <tr><td colspan="9" class="text-center">Data tidak ditemukan.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
$ref_kategori = $pdo->query("SELECT nama FROM ref_kategori ORDER BY nama ASC")->fetchAll(PDO::FETCH_ASSOC);
$ref_satuan = $pdo->query("SELECT nama FROM ref_satuan ORDER BY nama ASC")->fetchAll(PDO::FETCH_ASSOC);
$ref_kode = $pdo->query("SELECT kode, uraian FROM ref_kode_barang ORDER BY kode ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<datalist id="kode_list">
    <?php foreach($ref_kode as $rk): ?>
    <option value="<?= htmlspecialchars($rk['kode']) ?>"> <?= htmlspecialchars($rk['uraian']) ?></option>
    <?php endforeach; ?>
</datalist>

<!-- Modal Tambah -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post">
          <div class="modal-header">
            <h5 class="modal-title">Tambah Barang Baru</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="action" value="add">
            <div class="mb-3">
                <label>Kode Barang (Pilih/Ketik)</label>
                <input type="text" name="kode_barang" list="kode_list" class="form-control" placeholder="1.1.7... atau ketik nama" required>
                <small class="text-muted">Ketik kode atau nama barang untuk mencari dari referensi resmi.</small>
            </div>
            <div class="mb-3">
                <label>Nama Barang</label>
                <input type="text" name="nama_barang" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Spesifikasi (Opsional)</label>
                <textarea name="spesifikasi" class="form-control" rows="2" placeholder="Merk, ukuran, warna, dll"></textarea>
            </div>
            <div class="mb-3">
                <label>Kategori</label>
                <select name="kategori" class="form-select" required>
                    <option value="">-- Pilih --</option>
                    <?php foreach($ref_kategori as $rk): ?>
                    <option value="<?= htmlspecialchars($rk['nama']) ?>"><?= htmlspecialchars($rk['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label>Satuan</label>
                <select name="satuan" class="form-select" required>
                    <option value="">-- Pilih --</option>
                    <?php foreach($ref_satuan as $rs): ?>
                    <option value="<?= htmlspecialchars($rs['nama']) ?>"><?= htmlspecialchars($rs['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label>Harga Satuan (Rp)</label>
                <input type="number" name="harga_satuan" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Stok Awal</label>
                <input type="number" name="stok_awal" class="form-control" value="0" required>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary">Simpan</button>
          </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Edit -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post">
          <div class="modal-header">
            <h5 class="modal-title">Edit Master Barang</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="e_id">
            <div class="mb-3">
                <label>Kode Barang (Pilih/Ketik)</label>
                <input type="text" name="kode_barang" id="e_kode" list="kode_list" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Nama Barang</label>
                <input type="text" name="nama_barang" id="e_nama" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Spesifikasi (Opsional)</label>
                <textarea name="spesifikasi" id="e_spek" class="form-control" rows="2"></textarea>
            </div>
            <div class="mb-3">
                <label>Kategori</label>
                <select name="kategori" id="e_kat" class="form-select" required>
                    <option value="">-- Pilih --</option>
                    <?php foreach($ref_kategori as $rk): ?>
                    <option value="<?= htmlspecialchars($rk['nama']) ?>"><?= htmlspecialchars($rk['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label>Satuan</label>
                <select name="satuan" id="e_satuan" class="form-select" required>
                    <option value="">-- Pilih --</option>
                    <?php foreach($ref_satuan as $rs): ?>
                    <option value="<?= htmlspecialchars($rs['nama']) ?>"><?= htmlspecialchars($rs['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label>Harga Satuan (Rp)</label>
                <input type="number" name="harga_satuan" id="e_harga" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Stok Awal</label>
                <input type="number" name="stok_awal" id="e_stok" class="form-control" required>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-success">Update Barang</button>
          </div>
      </form>
    </div>
  </div>
</div>

<script>
function editBarang(btn) {
    document.getElementById('e_id').value = btn.getAttribute('data-id');
    document.getElementById('e_kode').value = btn.getAttribute('data-kode');
    document.getElementById('e_nama').value = btn.getAttribute('data-nama');
    document.getElementById('e_spek').value = btn.getAttribute('data-spek');
    document.getElementById('e_kat').value = btn.getAttribute('data-kat');
    document.getElementById('e_satuan').value = btn.getAttribute('data-satuan');
    document.getElementById('e_harga').value = btn.getAttribute('data-harga');
    document.getElementById('e_stok').value = btn.getAttribute('data-stok');
    
    var editModal = new bootstrap.Modal(document.getElementById('editModal'));
    editModal.show();
}
</script>

<?php include 'layout_footer.php'; ?>
