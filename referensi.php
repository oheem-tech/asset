<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $table = $_POST['table'];
    $action = $_POST['action'];
    
    // Ensure table is safe
    $allowed_tables = ['ref_kategori', 'ref_satuan', 'ref_penerima'];
    if (in_array($table, $allowed_tables)) {
        if ($action == 'add' && !empty($_POST['nama'])) {
            $stmt = $pdo->prepare("INSERT INTO $table (nama) VALUES (?)");
            $stmt->execute([$_POST['nama']]);
        } elseif ($action == 'edit' && !empty($_POST['id']) && !empty($_POST['nama'])) {
            $new_nama = $_POST['nama'];
            $id = $_POST['id'];
            
            // Ambil nama lama sebelum diupdate
            $stmt = $pdo->prepare("SELECT nama FROM $table WHERE id = ?");
            $stmt->execute([$id]);
            $old = $stmt->fetch(PDO::FETCH_ASSOC);
            $old_nama = $old ? $old['nama'] : '';
            
            // Update nama di tabel referensi
            $stmt = $pdo->prepare("UPDATE $table SET nama = ? WHERE id = ?");
            $stmt->execute([$new_nama, $id]);
            
            // Sinkronisasi (Cascade Update) ke tabel yang terhubung
            if ($old_nama !== '' && $old_nama !== $new_nama) {
                if ($table == 'ref_kategori') {
                    $upd = $pdo->prepare("UPDATE barang SET kategori = ? WHERE kategori = ?");
                    $upd->execute([$new_nama, $old_nama]);
                } elseif ($table == 'ref_satuan') {
                    $upd = $pdo->prepare("UPDATE barang SET satuan = ? WHERE satuan = ?");
                    $upd->execute([$new_nama, $old_nama]);
                } elseif ($table == 'ref_penerima') {
                    $upd = $pdo->prepare("UPDATE transaksi SET keterangan = ? WHERE keterangan = ?");
                    $upd->execute([$new_nama, $old_nama]);
                }
            }
        } elseif ($action == 'delete' && !empty($_POST['id'])) {
            $stmt = $pdo->prepare("DELETE FROM $table WHERE id = ?");
            $stmt->execute([$_POST['id']]);
        }
    }
    header("Location: referensi.php");
    exit;
}

include 'layout_header.php';

function getRefs($pdo, $table) {
    return $pdo->query("SELECT * FROM $table ORDER BY nama ASC")->fetchAll(PDO::FETCH_ASSOC);
}

$kategori = getRefs($pdo, 'ref_kategori');
$satuan = getRefs($pdo, 'ref_satuan');
$penerima = getRefs($pdo, 'ref_penerima');
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2">Data Referensi</h1>
</div>

<div class="row">
    <!-- Kategori -->
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white fw-bold">Kategori BHP</div>
            <div class="card-body">
                <form method="post" class="d-flex gap-2 mb-3">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="table" value="ref_kategori">
                    <input type="text" name="nama" class="form-control form-control-sm" placeholder="Kategori baru..." required>
                    <button type="submit" class="btn btn-primary btn-sm">Tambah</button>
                </form>
                <ul class="list-group list-group-flush" style="max-height: 300px; overflow-y: auto;">
                    <?php foreach($kategori as $k): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center p-1">
                        <?= htmlspecialchars($k['nama']) ?>
                        <div class="d-flex gap-1">
                            <button type="button" class="btn btn-sm text-warning border-0 bg-transparent" onclick="editRef('ref_kategori', <?= $k['id'] ?>, '<?= htmlspecialchars($k['nama'], ENT_QUOTES) ?>')"><i class="bi bi-pencil"></i></button>
                            <form method="post" onsubmit="return confirm('Hapus referensi ini?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="table" value="ref_kategori">
                                <input type="hidden" name="id" value="<?= $k['id'] ?>">
                                <button type="submit" class="btn btn-sm text-danger border-0 bg-transparent"><i class="bi bi-x-circle"></i></button>
                            </form>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Satuan -->
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white fw-bold">Satuan Barang</div>
            <div class="card-body">
                <form method="post" class="d-flex gap-2 mb-3">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="table" value="ref_satuan">
                    <input type="text" name="nama" class="form-control form-control-sm" placeholder="Satuan baru..." required>
                    <button type="submit" class="btn btn-primary btn-sm">Tambah</button>
                </form>
                <ul class="list-group list-group-flush" style="max-height: 300px; overflow-y: auto;">
                    <?php foreach($satuan as $k): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center p-1">
                        <?= htmlspecialchars($k['nama']) ?>
                        <div class="d-flex gap-1">
                            <button type="button" class="btn btn-sm text-warning border-0 bg-transparent" onclick="editRef('ref_satuan', <?= $k['id'] ?>, '<?= htmlspecialchars($k['nama'], ENT_QUOTES) ?>')"><i class="bi bi-pencil"></i></button>
                            <form method="post" onsubmit="return confirm('Hapus referensi ini?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="table" value="ref_satuan">
                                <input type="hidden" name="id" value="<?= $k['id'] ?>">
                                <button type="submit" class="btn btn-sm text-danger border-0 bg-transparent"><i class="bi bi-x-circle"></i></button>
                            </form>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Penerima -->
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white fw-bold">Unit / Penerima</div>
            <div class="card-body">
                <form method="post" class="d-flex gap-2 mb-3">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="table" value="ref_penerima">
                    <input type="text" name="nama" class="form-control form-control-sm" placeholder="Nama / Unit penerima..." required>
                    <button type="submit" class="btn btn-primary btn-sm">Tambah</button>
                </form>
                <ul class="list-group list-group-flush" style="max-height: 300px; overflow-y: auto;">
                    <?php foreach($penerima as $k): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center p-1">
                        <?= htmlspecialchars($k['nama']) ?>
                        <div class="d-flex gap-1">
                            <button type="button" class="btn btn-sm text-warning border-0 bg-transparent" onclick="editRef('ref_penerima', <?= $k['id'] ?>, '<?= htmlspecialchars($k['nama'], ENT_QUOTES) ?>')"><i class="bi bi-pencil"></i></button>
                            <form method="post" onsubmit="return confirm('Hapus referensi ini?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="table" value="ref_penerima">
                                <input type="hidden" name="id" value="<?= $k['id'] ?>">
                                <button type="submit" class="btn btn-sm text-danger border-0 bg-transparent"><i class="bi bi-x-circle"></i></button>
                            </form>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit Referensi -->
<div class="modal fade" id="editRefModal" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <form method="post">
          <div class="modal-header">
            <h5 class="modal-title">Edit Referensi</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="table" id="ref_table">
            <input type="hidden" name="id" id="ref_id">
            <div class="mb-3">
                <label>Nama Referensi</label>
                <input type="text" name="nama" id="ref_nama" class="form-control" required>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-success btn-sm">Simpan</button>
          </div>
      </form>
    </div>
  </div>
</div>

<script>
function editRef(table, id, nama) {
    document.getElementById('ref_table').value = table;
    document.getElementById('ref_id').value = id;
    document.getElementById('ref_nama').value = nama;
    var editModal = new bootstrap.Modal(document.getElementById('editRefModal'));
    editModal.show();
}
</script>

<?php include 'layout_footer.php'; ?>
