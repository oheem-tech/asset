<?php
require_once 'config.php';

$error_msg = '';
$success_msg = '';

// Handle CRUD
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'add') {
        $kode = $_POST['kode'];
        $uraian = $_POST['uraian'];
        try {
            $stmt = $pdo->prepare("INSERT INTO ref_kode_barang (kode, uraian) VALUES (?, ?)");
            $stmt->execute([$kode, $uraian]);
            $success_msg = "Kodefikasi berhasil ditambahkan.";
        } catch (PDOException $e) {
            $error_msg = "Gagal: Kode barang mungkin sudah ada.";
        }
    } elseif ($action == 'edit') {
        $old_kode = $_POST['old_kode'];
        $new_kode = $_POST['kode'];
        $uraian = $_POST['uraian'];
        
        try {
            // Update ref_kode_barang
            $stmt = $pdo->prepare("UPDATE ref_kode_barang SET kode = ?, uraian = ? WHERE kode = ?");
            $stmt->execute([$new_kode, $uraian, $old_kode]);
            
            // Cascade update ke tabel barang
            if ($old_kode !== $new_kode) {
                $upd = $pdo->prepare("UPDATE barang SET kode_barang = ? WHERE kode_barang = ?");
                $upd->execute([$new_kode, $old_kode]);
            }
            $success_msg = "Kodefikasi berhasil diperbarui.";
        } catch (PDOException $e) {
            $error_msg = "Gagal mengupdate: Kode baru mungkin sudah ada atau terjadi kesalahan.";
        }
    } elseif ($action == 'delete') {
        $kode = $_POST['kode'];
        try {
            $stmt = $pdo->prepare("DELETE FROM ref_kode_barang WHERE kode = ?");
            $stmt->execute([$kode]);
            $success_msg = "Kodefikasi berhasil dihapus.";
        } catch (PDOException $e) {
            $error_msg = "Gagal menghapus data.";
        }
    }
}

// Pagination & Search settings
$limit = 50;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$search = isset($_GET['q']) ? $_GET['q'] : '';

// Query building
$where = "";
$params = [];
if ($search !== '') {
    $where = "WHERE kode LIKE ? OR uraian LIKE ?";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Get total for pagination
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM ref_kode_barang $where");
$count_stmt->execute($params);
$total_rows = $count_stmt->fetchColumn();
$total_pages = ceil($total_rows / $limit);

// Get data
$sql = "SELECT * FROM ref_kode_barang $where ORDER BY kode ASC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$kode_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'layout_header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2">Kodefikasi Barang</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">Tambah Kode Baru</button>
</div>

<?php if($error_msg): ?>
<div class="alert alert-danger"><?= htmlspecialchars($error_msg) ?></div>
<?php endif; ?>
<?php if($success_msg): ?>
<div class="alert alert-success"><?= htmlspecialchars($success_msg) ?></div>
<?php endif; ?>

<!-- Search Form -->
<form method="get" class="mb-3 d-flex gap-2 w-50">
    <input type="text" name="q" class="form-control" placeholder="Cari kode atau nama barang..." value="<?= htmlspecialchars($search) ?>">
    <button type="submit" class="btn btn-outline-secondary">Cari</button>
    <?php if($search): ?>
    <a href="kodefikasi.php" class="btn btn-outline-danger">Reset</a>
    <?php endif; ?>
</form>

<div class="alert alert-info">
    Menampilkan <?= count($kode_list) ?> dari total <?= number_format($total_rows, 0, ',', '.') ?> data kodefikasi.
</div>

<div class="table-responsive">
    <table class="table table-striped table-bordered table-sm align-middle">
        <thead class="table-dark">
            <tr>
                <th width="30%">Kode Barang</th>
                <th width="60%">Uraian / Nama Kategori</th>
                <th width="10%">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($kode_list as $k): ?>
            <tr>
                <td><?= htmlspecialchars($k['kode']) ?></td>
                <td><?= htmlspecialchars($k['uraian']) ?></td>
                <td>
                    <div class="d-flex flex-nowrap gap-1">
                        <button type="button" class="btn btn-warning btn-sm" 
                            data-kode="<?= htmlspecialchars($k['kode']) ?>"
                            data-uraian="<?= htmlspecialchars($k['uraian']) ?>"
                            onclick="editKode(this)" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </button>
                        
                        <form method="post" onsubmit="return confirm('Hapus kodefikasi ini?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="kode" value="<?= htmlspecialchars($k['kode']) ?>">
                            <button type="submit" class="btn btn-danger btn-sm" title="Hapus"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($kode_list)): ?>
            <tr><td colspan="3" class="text-center">Data tidak ditemukan.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
<nav>
  <ul class="pagination pagination-sm justify-content-center">
    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
      <a class="page-link" href="?page=<?= $page - 1 ?>&q=<?= urlencode($search) ?>">Previous</a>
    </li>
    
    <?php 
    $start_page = max(1, $page - 3);
    $end_page = min($total_pages, $page + 3);
    for ($i = $start_page; $i <= $end_page; $i++): 
    ?>
    <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
      <a class="page-link" href="?page=<?= $i ?>&q=<?= urlencode($search) ?>"><?= $i ?></a>
    </li>
    <?php endfor; ?>
    
    <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
      <a class="page-link" href="?page=<?= $page + 1 ?>&q=<?= urlencode($search) ?>">Next</a>
    </li>
  </ul>
</nav>
<?php endif; ?>

<!-- Modal Tambah -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post">
          <div class="modal-header">
            <h5 class="modal-title">Tambah Kodefikasi Baru</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="action" value="add">
            <div class="mb-3">
                <label>Kode Barang</label>
                <input type="text" name="kode" class="form-control" placeholder="Contoh: 1.1.7.01.01.01.999" required>
            </div>
            <div class="mb-3">
                <label>Uraian / Nama Kategori</label>
                <input type="text" name="uraian" class="form-control" required>
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
            <h5 class="modal-title">Edit Kodefikasi</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="old_kode" id="e_old_kode">
            <div class="mb-3">
                <label>Kode Barang</label>
                <input type="text" name="kode" id="e_kode" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Uraian / Nama Kategori</label>
                <input type="text" name="uraian" id="e_uraian" class="form-control" required>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-success">Update</button>
          </div>
      </form>
    </div>
  </div>
</div>

<script>
function editKode(btn) {
    var kode = btn.getAttribute('data-kode');
    var uraian = btn.getAttribute('data-uraian');
    
    document.getElementById('e_old_kode').value = kode;
    document.getElementById('e_kode').value = kode;
    document.getElementById('e_uraian').value = uraian;
    
    var editModal = new bootstrap.Modal(document.getElementById('editModal'));
    editModal.show();
}
</script>

<?php include 'layout_footer.php'; ?>
