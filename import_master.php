<?php
require_once 'config.php';

$error_msg = '';
$success_msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'execute_import') {
    $data_json = $_POST['import_data'];
    $data = json_decode($data_json, true);
    
    if (is_array($data) && count($data) > 0) {
        $sukses = 0;
        $gagal = 0;
        
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO barang (kode_barang, nama_barang, spesifikasi, kategori, satuan, harga_satuan, stok_awal) VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            // Get existing codes to prevent duplicate errors breaking the transaction if we just skip them
            $existing = $pdo->query("SELECT kode_barang FROM barang")->fetchAll(PDO::FETCH_COLUMN);
            $existing_codes = array_map('strtolower', $existing);
            
            foreach ($data as $row) {
                // Ensure at least kode and nama are present
                if (empty($row['kode_barang']) || empty($row['nama_barang'])) {
                    $gagal++;
                    continue;
                }
                
                // Skip if duplicate code
                if (in_array(strtolower($row['kode_barang']), $existing_codes)) {
                    $gagal++;
                    continue;
                }
                
                $harga = (float)str_replace(['Rp', '.', ',', ' '], '', $row['harga_satuan']);
                $stok = (int)$row['stok_awal'];
                
                $stmt->execute([
                    $row['kode_barang'],
                    $row['nama_barang'],
                    isset($row['spesifikasi']) ? $row['spesifikasi'] : '',
                    isset($row['kategori']) ? $row['kategori'] : '-',
                    isset($row['satuan']) ? $row['satuan'] : 'Pcs',
                    $harga,
                    $stok
                ]);
                $sukses++;
                $existing_codes[] = strtolower($row['kode_barang']); // add to runtime array to prevent duplicates within same batch
            }
            $pdo->commit();
            header("Location: master.php?msg=" . urlencode("Berhasil impor $sukses barang. Gagal/Dilewati: $gagal barang (karena kode duplikat atau data tidak lengkap)."));
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_msg = "Terjadi kesalahan sistem: " . $e->getMessage();
        }
    } else {
        $error_msg = "Data kosong atau format tidak valid.";
    }
}

include 'layout_header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2">Impor Master Barang</h1>
    <a href="master.php" class="btn btn-secondary">Kembali</a>
</div>

<?php if($error_msg): ?>
<div class="alert alert-danger"><?= htmlspecialchars($error_msg) ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white fw-bold">1. Copy-Paste Data dari Excel</div>
            <div class="card-body">
                <div class="alert alert-info">
                    <strong>Cara Penggunaan:</strong> Buka file Excel Anda, blok dan copy data (baris & kolom), lalu paste ke dalam kotak di bawah ini.<br>
                    <strong>Urutan Kolom Wajib:</strong> Kode Barang | Nama Barang | Kategori | Spesifikasi | Satuan | Harga Satuan | Stok Awal<br>
                    <small>*(Jangan ikut copy baris judul/header, cukup baris datanya saja)*</small>
                </div>
                <textarea id="paste_area" class="form-control mb-3" rows="8" placeholder="Paste data Excel Anda di sini..."></textarea>
                <button id="btn_preview" class="btn btn-warning w-100 fw-bold">Preview Data</button>
            </div>
        </div>

        <div class="card d-none" id="preview_card">
            <div class="card-header bg-success text-white fw-bold">2. Preview & Eksekusi Impor</div>
            <div class="card-body">
                <div class="table-responsive mb-3" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-bordered table-sm table-striped text-nowrap" id="preview_table">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Kode Barang</th>
                                <th>Nama Barang</th>
                                <th>Kategori</th>
                                <th>Spesifikasi</th>
                                <th>Satuan</th>
                                <th>Harga Satuan</th>
                                <th>Stok Awal</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                
                <form method="post" id="form_execute">
                    <input type="hidden" name="action" value="execute_import">
                    <input type="hidden" name="import_data" id="import_data_field">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-success" id="count_valid">0 Valid</span>
                            <span class="badge bg-danger" id="count_invalid">0 Tidak Valid</span>
                        </div>
                        <button type="button" id="btn_execute" class="btn btn-success fw-bold">Eksekusi Impor (<span id="count_to_import">0</span> Data)</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('btn_preview').addEventListener('click', function() {
    const raw = document.getElementById('paste_area').value.trim();
    if (!raw) {
        alert('Teks area masih kosong!');
        return;
    }
    
    // Split rows by newline
    const rows = raw.split(/\r?\n/);
    const tbody = document.querySelector('#preview_table tbody');
    tbody.innerHTML = '';
    
    let validCount = 0;
    let invalidCount = 0;
    let validData = [];
    
    rows.forEach((row, index) => {
        if (!row.trim()) return;
        
        // Split columns by tab
        const cols = row.split('\t');
        
        let tr = document.createElement('tr');
        
        // Column mapping: 
        // 0: Kode, 1: Nama, 2: Kategori, 3: Spesifikasi, 4: Satuan, 5: Harga, 6: Stok
        let kode = cols[0] ? cols[0].trim() : '';
        let nama = cols[1] ? cols[1].trim() : '';
        let kategori = cols[2] ? cols[2].trim() : '-';
        let spek = cols[3] ? cols[3].trim() : '';
        let satuan = cols[4] ? cols[4].trim() : 'Pcs';
        let harga = cols[5] ? cols[5].trim() : '0';
        let stok = cols[6] ? cols[6].trim() : '0';
        
        let isValid = true;
        let errMsg = [];
        
        if (!kode) { isValid = false; errMsg.push('Kode Kosong'); }
        if (!nama) { isValid = false; errMsg.push('Nama Kosong'); }
        
        if (isValid) {
            validCount++;
            tr.innerHTML = `
                <td>${index+1}</td>
                <td class="text-success fw-bold">${kode}</td>
                <td>${nama}</td>
                <td>${kategori}</td>
                <td>${spek}</td>
                <td>${satuan}</td>
                <td>${harga}</td>
                <td>${stok}</td>
                <td><span class="badge bg-success">Valid</span></td>
            `;
            validData.push({
                kode_barang: kode,
                nama_barang: nama,
                kategori: kategori,
                spesifikasi: spek,
                satuan: satuan,
                harga_satuan: harga,
                stok_awal: stok
            });
        } else {
            invalidCount++;
            tr.classList.add('table-danger');
            tr.innerHTML = `
                <td>${index+1}</td>
                <td>${kode}</td>
                <td>${nama}</td>
                <td>${kategori}</td>
                <td>${spek}</td>
                <td>${satuan}</td>
                <td>${harga}</td>
                <td>${stok}</td>
                <td><span class="badge bg-danger">${errMsg.join(', ')}</span></td>
            `;
        }
        tbody.appendChild(tr);
    });
    
    document.getElementById('count_valid').innerText = validCount + ' Valid';
    document.getElementById('count_invalid').innerText = invalidCount + ' Tidak Valid / Dilewati';
    document.getElementById('count_to_import').innerText = validCount;
    
    document.getElementById('import_data_field').value = JSON.stringify(validData);
    document.getElementById('preview_card').classList.remove('d-none');
});

document.getElementById('btn_execute').addEventListener('click', function() {
    const dataField = document.getElementById('import_data_field').value;
    if (dataField === '[]' || !dataField) {
        alert('Tidak ada data valid yang bisa diimpor.');
        return;
    }
    
    if (confirm('Anda yakin ingin mengeksekusi impor data ini?')) {
        document.getElementById('form_execute').submit();
    }
});
</script>

<?php include 'layout_footer.php'; ?>
