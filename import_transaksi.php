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
            $stmt = $pdo->prepare("INSERT INTO transaksi (tanggal, jenis, barang_id, jumlah, keterangan, bukti) VALUES (?, ?, ?, ?, ?, ?)");
            
            // Get mapping of kode_barang -> id
            $barang_map = [];
            $b_query = $pdo->query("SELECT id, kode_barang FROM barang")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($b_query as $b) {
                $barang_map[strtolower($b['kode_barang'])] = $b['id'];
            }
            
            foreach ($data as $row) {
                $kode = strtolower(trim($row['kode_barang']));
                
                // Skip if kode_barang not found in master
                if (!isset($barang_map[$kode])) {
                    $gagal++;
                    continue;
                }
                
                $barang_id = $barang_map[$kode];
                
                $stmt->execute([
                    $row['tanggal'],
                    $row['jenis'],
                    $barang_id,
                    (int)$row['jumlah'],
                    $row['keterangan'],
                    $row['bukti']
                ]);
                $sukses++;
            }
            $pdo->commit();
            header("Location: transaksi.php?msg=" . urlencode("Berhasil impor $sukses riwayat transaksi. Gagal/Dilewati: $gagal data (kemungkinan kode barang tidak ditemukan di Master Barang)."));
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_msg = "Terjadi kesalahan sistem: " . $e->getMessage();
        }
    } else {
        $error_msg = "Data kosong atau format tidak valid.";
    }
}

// Fetch existing codes for JS validation
$existing = $pdo->query("SELECT kode_barang FROM barang")->fetchAll(PDO::FETCH_COLUMN);
$valid_codes = array_map('strtolower', $existing);

include 'layout_header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2">Impor Riwayat Transaksi</h1>
    <a href="transaksi.php" class="btn btn-secondary">Kembali</a>
</div>

<?php if($error_msg): ?>
<div class="alert alert-danger"><?= htmlspecialchars($error_msg) ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white fw-bold">1. Copy-Paste Data Riwayat Transaksi dari Excel</div>
            <div class="card-body">
                <div class="alert alert-info">
                    <strong>PENTING:</strong> Pastikan Anda sudah mengimpor/memasukkan <strong>Master Barang</strong> terlebih dahulu. Sistem akan menolak transaksi jika Kode Barang tidak ditemukan di Master Barang.<br><br>
                    <strong>Urutan Kolom Wajib (6 Kolom):</strong><br>
                    Tanggal (YYYY-MM-DD) | Jenis (Masuk / Keluar) | Kode Barang | Jumlah | Keterangan (Penerima/Sumber) | No Dokumen/Bukti<br>
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
                                <th>Tanggal</th>
                                <th>Jenis</th>
                                <th>Kode Barang</th>
                                <th>Jumlah</th>
                                <th>Keterangan</th>
                                <th>No Bukti</th>
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
// Pass valid codes from PHP to JS
const validCodes = <?= json_encode($valid_codes) ?>;

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
        // 0: Tanggal, 1: Jenis, 2: Kode Barang, 3: Jumlah, 4: Keterangan, 5: Bukti
        let tanggal = cols[0] ? cols[0].trim() : '';
        let jenis = cols[1] ? cols[1].trim() : '';
        let kode = cols[2] ? cols[2].trim() : '';
        let jumlah = cols[3] ? cols[3].trim() : '0';
        let ket = cols[4] ? cols[4].trim() : '';
        let bukti = cols[5] ? cols[5].trim() : '';
        
        let isValid = true;
        let errMsg = [];
        
        // Normalize jenis
        let lowerJenis = jenis.toLowerCase();
        if (lowerJenis.includes('masuk')) jenis = 'Masuk';
        else if (lowerJenis.includes('keluar')) jenis = 'Keluar';
        else { isValid = false; errMsg.push('Jenis tidak dikenali (Harus Masuk/Keluar)'); }
        
        if (!tanggal.match(/^\d{4}-\d{2}-\d{2}$/)) { 
            // Coba perbaiki format DD-MM-YYYY ke YYYY-MM-DD sederhana jika mungkin, tapi kita wajibkan standar
            isValid = false; 
            errMsg.push('Format Tanggal Harus YYYY-MM-DD'); 
        }
        
        if (!kode) { 
            isValid = false; errMsg.push('Kode Kosong'); 
        } else if (!validCodes.includes(kode.toLowerCase())) {
            isValid = false; errMsg.push('Kode Barang Belum Terdaftar di Master'); 
        }
        
        if (parseInt(jumlah) <= 0 || isNaN(parseInt(jumlah))) {
            isValid = false; errMsg.push('Jumlah tidak valid');
        }
        
        if (isValid) {
            validCount++;
            tr.innerHTML = `
                <td>${index+1}</td>
                <td>${tanggal}</td>
                <td class="fw-bold ${jenis == 'Masuk' ? 'text-success' : 'text-danger'}">${jenis}</td>
                <td class="fw-bold">${kode}</td>
                <td>${jumlah}</td>
                <td>${ket}</td>
                <td>${bukti}</td>
                <td><span class="badge bg-success">Valid</span></td>
            `;
            validData.push({
                tanggal: tanggal,
                jenis: jenis,
                kode_barang: kode,
                jumlah: parseInt(jumlah),
                keterangan: ket,
                bukti: bukti
            });
        } else {
            invalidCount++;
            tr.classList.add('table-danger');
            tr.innerHTML = `
                <td>${index+1}</td>
                <td>${tanggal}</td>
                <td>${jenis}</td>
                <td class="fw-bold">${kode}</td>
                <td>${jumlah}</td>
                <td>${ket}</td>
                <td>${bukti}</td>
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
