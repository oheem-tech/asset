<?php
require_once 'config.php';
require_once 'terbilang.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'save_dokumen') {
    $bukti = $_POST['bukti_transaksi'];
    
    // Check if exists
    $cek = $pdo->prepare("SELECT COUNT(*) FROM dokumen_pengeluaran WHERE bukti_transaksi = ?");
    $cek->execute([$bukti]);
    
    if ($cek->fetchColumn() > 0) {
        $stmt = $pdo->prepare("UPDATE dokumen_pengeluaran SET no_npb=?, tgl_npb=?, no_spb=?, tgl_spb=?, no_sppb=?, tgl_sppb=?, no_bast=?, tgl_bast=?, pemohon=?, pihak_pertama_id=? WHERE bukti_transaksi=?");
        $stmt->execute([
            $_POST['no_npb'], $_POST['tgl_npb'], $_POST['no_spb'], $_POST['tgl_spb'], 
            $_POST['no_sppb'], $_POST['tgl_sppb'], $_POST['no_bast'], $_POST['tgl_bast'], $_POST['pemohon'], $_POST['pihak_pertama_id'], $bukti
        ]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO dokumen_pengeluaran (bukti_transaksi, no_npb, tgl_npb, no_spb, tgl_spb, no_sppb, tgl_sppb, no_bast, tgl_bast, pemohon, pihak_pertama_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $bukti, $_POST['no_npb'], $_POST['tgl_npb'], $_POST['no_spb'], $_POST['tgl_spb'], 
            $_POST['no_sppb'], $_POST['tgl_sppb'], $_POST['no_bast'], $_POST['tgl_bast'], $_POST['pemohon'], $_POST['pihak_pertama_id']
        ]);
    }
    
    $j = isset($_GET['jenis']) ? $_GET['jenis'] : 'npb';
    header("Location: laporan.php?jenis=" . urlencode($j) . "&trx_bukti=" . urlencode($bukti));
    exit;
}
include 'layout_header.php';

$jenis_laporan = isset($_GET['jenis']) ? $_GET['jenis'] : 'kartu';
$barang_id = isset($_GET['barang_id']) ? $_GET['barang_id'] : '';

// Load Pengaturan Kop
$kop = [];
$r = $pdo->query("SELECT kunci, nilai FROM pengaturan")->fetchAll(PDO::FETCH_ASSOC);
foreach ($r as $row) $kop[$row['kunci']] = $row['nilai'];

// Helper for rendering signatures
function render_ttd($pdo, $jenis_laporan, $dinamis_text = '') {
    $stmt = $pdo->prepare("SELECT t.*, p.nama, p.nip FROM pengaturan_ttd t LEFT JOIN ref_pejabat p ON t.pejabat_id = p.id WHERE t.jenis_laporan = ? ORDER BY t.urutan ASC");
    $stmt->execute([$jenis_laporan]);
    $ttds = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($ttds) == 0) return ""; // No signatures plotted
    
    $html = '<div class="d-flex justify-content-between mt-5 pt-4 text-center" style="page-break-inside: avoid;">';
    foreach ($ttds as $t) {
        $nama = $t['is_dinamis'] ? $dinamis_text : $t['nama'];
        $nip = ($t['is_dinamis'] || empty($t['nip'])) ? '' : '<br>NIP. ' . $t['nip'];
        $html .= '<div>';
        $html .= htmlspecialchars($t['jabatan']) . ',';
        $html .= '<br><br><br><br><br>';
        $html .= '<strong><u>' . htmlspecialchars($nama) . '</u></strong>';
        $html .= $nip;
        $html .= '</div>';
    }
    $html .= '</div>';
    return $html;
}

// Render Kop Surat HTML
$jenis_kop = isset($kop['jenis_kop']) ? $kop['jenis_kop'] : 'teks';

if ($jenis_kop == 'gambar' && !empty($kop['kop_banner'])) {
    $kop_html = '
    <div class="text-center" style="margin-top: 0; padding-top: 0;">
        <img src="' . htmlspecialchars($kop['kop_banner']) . '" style="max-width: 100%; height: auto; display: block; margin: 0 auto;">
        <div class="border-bottom border-dark border-3 mt-3 mb-1"></div>
        <div class="border-bottom border-dark border-1 mb-4"></div>
    </div>';
} else {
    $kop_html = '
    <div class="mb-4">
        <div class="d-flex align-items-center border-bottom border-dark border-3 pb-3 mb-1">
            <div style="width: 15%; text-align: center;">
                ' . (!empty($kop['logo_kiri']) ? '<img src="' . htmlspecialchars($kop['logo_kiri']) . '" style="max-height: 100px;">' : '') . '
            </div>
            <div style="width: 85%; text-align: center;">
                <div style="font-size: 16px;">' . htmlspecialchars(isset($kop['kop_1']) ? $kop['kop_1'] : '') . '</div>
                <div style="font-size: 20px; font-weight: bold;">' . htmlspecialchars(isset($kop['kop_2']) ? $kop['kop_2'] : '') . '</div>
                <div style="font-size: 24px; font-weight: bold;">' . htmlspecialchars(isset($kop['kop_3']) ? $kop['kop_3'] : '') . '</div>
                <div style="font-size: 14px;">' . htmlspecialchars(isset($kop['kop_4']) ? $kop['kop_4'] : '') . '</div>
            </div>
        </div>
        <div class="border-bottom border-dark border-1 mb-4"></div>
    </div>';
}

$barang_list = $pdo->query("SELECT id, nama_barang FROM barang ORDER BY nama_barang ASC")->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom d-print-none">
    <h1 class="h2">Laporan & Rekap</h1>
    <button class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i> Cetak Dokumen</button>
</div>

<form method="get" class="row g-3 mb-4 d-print-none">
    <div class="col-md-4">
        <label>Jenis Laporan</label>
        <select name="jenis" class="form-select" onchange="this.form.submit()">
            <option value="kartu" <?= $jenis_laporan == 'kartu' ? 'selected' : '' ?>>Kartu Barang Persediaan</option>
            <option value="penerimaan" <?= $jenis_laporan == 'penerimaan' ? 'selected' : '' ?>>Buku Penerimaan Barang</option>
            <option value="pengeluaran" <?= $jenis_laporan == 'pengeluaran' ? 'selected' : '' ?>>Buku Pengeluaran Persediaan</option>
            <option value="penyaluran" <?= $jenis_laporan == 'penyaluran' ? 'selected' : '' ?>>Buku Penyaluran Persediaan</option>
            <option value="opname" <?= $jenis_laporan == 'opname' ? 'selected' : '' ?>>Berita Acara Stock Opname</option>
            <option value="mutasi" <?= $jenis_laporan == 'mutasi' ? 'selected' : '' ?>>Daftar Mutasi BHP (Rekap Total)</option>
            <option value="bast" <?= $jenis_laporan == 'bast' ? 'selected' : '' ?>>Berita Acara Pemeriksaan Barang</option>
            <option value="npb" <?= $jenis_laporan == 'npb' ? 'selected' : '' ?>>Cetak NPB (Nota Permintaan Barang)</option>
            <option value="spb" <?= $jenis_laporan == 'spb' ? 'selected' : '' ?>>Cetak SPB (Surat Permintaan Barang)</option>
            <option value="sppb" <?= $jenis_laporan == 'sppb' ? 'selected' : '' ?>>Cetak SPPB (Surat Perintah Penyaluran)</option>
            <option value="bast_keluar" <?= $jenis_laporan == 'bast_keluar' ? 'selected' : '' ?>>Cetak BAST Pengeluaran Barang</option>
        </select>
    </div>
    
    <?php if ($jenis_laporan == 'kartu'): ?>
    <div class="col-md-6">
        <label>Pilih Barang</label>
        <select name="barang_id" class="form-select" onchange="this.form.submit()">
            <option value="">-- Pilih --</option>
            <?php foreach ($barang_list as $b): ?>
            <option value="<?= $b['id'] ?>" <?= $barang_id == $b['id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['nama_barang']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>

    <?php 
    $is_dokumen_keluar = in_array($jenis_laporan, ['npb', 'spb', 'sppb', 'bast_keluar', 'dokumen_pengeluaran']);
    if ($jenis_laporan == 'bast' || $is_dokumen_keluar): 
        $where_trx = $is_dokumen_keluar ? "WHERE bukti != '' AND jenis = 'Keluar'" : "WHERE bukti != ''";
        $list_trx = $pdo->query("SELECT MAX(tanggal) as tanggal, MAX(jenis) as jenis, bukti, MAX(keterangan) as keterangan, COUNT(id) as total_item FROM transaksi $where_trx GROUP BY bukti ORDER BY tanggal DESC")->fetchAll(PDO::FETCH_ASSOC);
        $trx_bukti = isset($_GET['trx_bukti']) ? $_GET['trx_bukti'] : '';
    ?>
    <div class="col-md-8">
        <label>Pilih Dokumen / Transaksi (Berdasarkan No Bukti)</label>
        <select name="trx_bukti" class="form-select" onchange="this.form.submit()">
            <option value="">-- Pilih Nomor Bukti --</option>
            <?php foreach ($list_trx as $tr): ?>
            <option value="<?= htmlspecialchars($tr['bukti']) ?>" <?= $trx_bukti == $tr['bukti'] ? 'selected' : '' ?>>
                [<?= $tr['jenis'] ?>] <?= htmlspecialchars($tr['bukti']) ?> - <?= $tr['tanggal'] ?> (<?= htmlspecialchars($tr['keterangan']) ?>) - <?= $tr['total_item'] ?> Barang
            </option>
            <?php endforeach; ?>
        </select>
        <small class="text-muted">*Hanya menampilkan transaksi yang memiliki Nomor Bukti terisi.</small>
    </div>
    <?php endif; ?>
</form>

<div class="card border-0 shadow-sm mb-5">
<div class="card-body bg-white px-5 pt-0 pb-5" style="min-height: 297mm; max-width: 210mm; margin: 0 auto; color: #000;">

<?php if ($jenis_laporan == 'kartu' && $barang_id): 
    $stmt = $pdo->prepare("SELECT * FROM barang WHERE id = ?");
    $stmt->execute([$barang_id]);
    $barang = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $trx_stmt = $pdo->prepare("SELECT * FROM transaksi WHERE barang_id = ? ORDER BY tanggal ASC, id ASC");
    $trx_stmt->execute([$barang_id]);
    $histori = $trx_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stok_berjalan = $barang['stok_awal'];
?>
    <div class="doc-page doc-landscape">
    <?= $kop_html ?>
    <h4 class="text-center">KARTU BARANG PERSEDIAAN</h4>
    <p><strong>Nama Barang:</strong> <?= htmlspecialchars($barang['nama_barang']) ?><br>
       <strong>Satuan:</strong> <?= htmlspecialchars($barang['satuan']) ?></p>
    
    <table class="table table-bordered table-sm mt-3">
        <thead class="table-dark text-center align-middle">
            <tr>
                <th rowspan="2">Tanggal</th>
                <th rowspan="2">Nomor Bukti</th>
                <th rowspan="2">Asal / Tujuan</th>
                <th colspan="3">Mutasi</th>
                <th rowspan="2">Sisa Stok</th>
            </tr>
            <tr>
                <th>Masuk</th>
                <th>Keluar</th>
                <th>Harga Satuan (Rp)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td></td>
                <td></td>
                <td><strong>Stok Awal</strong></td>
                <td class="text-center">-</td>
                <td class="text-center">-</td>
                <td class="text-end"><?= number_format($barang['harga_satuan'], 0, ',', '.') ?></td>
                <td class="text-center fw-bold"><?= $stok_berjalan ?></td>
            </tr>
            <?php foreach($histori as $h): 
                if ($h['jenis'] == 'Masuk') {
                    $stok_berjalan += $h['jumlah'];
                    $in = $h['jumlah'];
                    $out = '-';
                } else {
                    $stok_berjalan -= $h['jumlah'];
                    $in = '-';
                    $out = $h['jumlah'];
                }
            ?>
            <tr>
                <td><?= $h['tanggal'] ?></td>
                <td><?= htmlspecialchars($h['bukti']) ?></td>
                <td><?= htmlspecialchars($h['keterangan']) ?></td>
                <td class="text-center"><?= $in ?></td>
                <td class="text-center"><?= $out ?></td>
                <td class="text-end"><?= number_format($barang['harga_satuan'], 0, ',', '.') ?></td>
                <td class="text-center fw-bold"><?= $stok_berjalan ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <?= render_ttd($pdo, 'kartu') ?>
    </div>

<?php elseif ($jenis_laporan == 'penerimaan'): 
    $penerimaan = $pdo->query("SELECT t.tanggal, t.bukti, t.jumlah, t.keterangan, b.kode_barang, b.nama_barang, b.spesifikasi, b.harga_satuan, b.satuan FROM transaksi t JOIN barang b ON t.barang_id = b.id WHERE t.jenis = 'Masuk' ORDER BY t.tanggal ASC")->fetchAll(PDO::FETCH_ASSOC);
    $total_seluruh = 0;
?>
    <div class="doc-page doc-landscape">
    <h4 class="text-center mb-4">BUKU PENERIMAAN BARANG<br>BULAN <?= strtoupper(date('F Y')) ?></h4>
    
    <table class="table table-bordered table-sm mt-3">
        <thead class="table-light text-center align-middle">
            <tr>
                <th rowspan="2" width="3%">No.</th>
                <th colspan="3">Dokumen</th>
                <th rowspan="2" width="10%">Kode Barang</th>
                <th rowspan="2" width="15%">Nama Barang</th>
                <th colspan="2">Spesifikasi Barang</th>
                <th rowspan="2" width="5%">Jumlah</th>
                <th rowspan="2" width="5%">Satuan<br>Barang</th>
                <th rowspan="2" width="8%">Harga<br>Satuan (Rp)</th>
                <th rowspan="2" width="8%">Nilai Total (Rp)</th>
                <th rowspan="2" width="10%">Keterangan</th>
            </tr>
            <tr>
                <th width="8%">Tanggal</th>
                <th width="12%">Nomor</th>
                <th width="10%">Nama</th>
                <th width="5%">NUSP</th>
                <th width="15%">Spesifikasi Nama<br>Barang</th>
            </tr>
        </thead>
        <tbody>
            <?php $no=1; foreach($penerimaan as $p): 
                $subtotal = $p['jumlah'] * $p['harga_satuan'];
                $total_seluruh += $subtotal;
            ?>
            <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td><?= $p['tanggal'] ?></td>
                <td><?= htmlspecialchars($p['bukti']) ?></td>
                <td><?= htmlspecialchars($p['keterangan']) ?></td>
                <td class="text-center"><?= htmlspecialchars(isset($p['kode_barang']) ? $p['kode_barang'] : '') ?></td>
                <td><?= htmlspecialchars($p['nama_barang']) ?></td>
                <td class="text-center">-</td>
                <td><?= htmlspecialchars(isset($p['spesifikasi']) ? $p['spesifikasi'] : '') ?></td>
                <td class="text-center"><?= $p['jumlah'] ?></td>
                <td class="text-center"><?= htmlspecialchars($p['satuan']) ?></td>
                <td class="text-end"><?= number_format($p['harga_satuan'], 0, ',', '.') ?></td>
                <td class="text-end"><?= number_format($subtotal, 0, ',', '.') ?></td>
                <td></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="table-secondary">
                <th colspan="11" class="text-end">TOTAL KESELURUHAN</th>
                <th class="text-end fw-bold"><?= number_format($total_seluruh, 0, ',', '.') ?></th>
                <th></th>
            </tr>
        </tfoot>
    </table>
    
    <?= render_ttd($pdo, 'penerimaan') ?>
    </div>

<?php elseif ($jenis_laporan == 'pengeluaran'): 
    $pengeluaran = $pdo->query("SELECT t.tanggal as tgl_transaksi, t.bukti, t.jumlah, t.keterangan, 
        b.kode_barang, b.nama_barang, b.spesifikasi, b.harga_satuan, b.satuan,
        dp.tgl_sppb, dp.no_sppb, dp.pemohon
        FROM transaksi t 
        JOIN barang b ON t.barang_id = b.id 
        LEFT JOIN dokumen_pengeluaran dp ON t.bukti = dp.bukti_transaksi 
        WHERE t.jenis = 'Keluar' ORDER BY t.tanggal ASC")->fetchAll(PDO::FETCH_ASSOC);
    $total_seluruh = 0;
?>
    <div class="doc-page doc-landscape">
    <h4 class="text-center mb-4">BUKU PENGELUARAN PERSEDIAAN<br>BULAN <?= strtoupper(date('F Y')) ?></h4>
    
    <table class="table table-bordered table-sm mt-3">
        <thead class="table-light text-center align-middle">
            <tr>
                <th rowspan="2" width="3%">No.</th>
                <th colspan="3">Dokumen</th>
                <th rowspan="2" width="10%">Kode Barang</th>
                <th rowspan="2" width="15%">Nama Barang</th>
                <th colspan="2">Spesifikasi Barang</th>
                <th rowspan="2" width="5%">Jumlah</th>
                <th rowspan="2" width="5%">Satuan<br>Barang</th>
                <th rowspan="2" width="8%">Harga<br>Satuan (Rp)</th>
                <th rowspan="2" width="8%">Nilai Total (Rp)</th>
                <th rowspan="2" width="10%">Keterangan</th>
            </tr>
            <tr>
                <th width="8%">Tanggal</th>
                <th width="12%">Nomor</th>
                <th width="10%">Nama</th>
                <th width="5%">NUSP</th>
                <th width="15%">Spesifikasi Nama<br>Barang</th>
            </tr>
        </thead>
        <tbody>
            <?php $no=1; foreach($pengeluaran as $p): 
                $subtotal = $p['jumlah'] * $p['harga_satuan'];
                $total_seluruh += $subtotal;
                $tgl = !empty($p['tgl_sppb']) ? $p['tgl_sppb'] : $p['tgl_transaksi'];
                $nomor = !empty($p['no_sppb']) ? $p['no_sppb'] : $p['bukti'];
                $nama_pemohon = !empty($p['pemohon']) ? $p['pemohon'] : $p['keterangan'];
            ?>
            <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td><?= $tgl ?></td>
                <td><?= htmlspecialchars($nomor) ?></td>
                <td><?= htmlspecialchars($nama_pemohon) ?></td>
                <td class="text-center"><?= htmlspecialchars(isset($p['kode_barang']) ? $p['kode_barang'] : '') ?></td>
                <td><?= htmlspecialchars($p['nama_barang']) ?></td>
                <td class="text-center">-</td>
                <td><?= htmlspecialchars(isset($p['spesifikasi']) ? $p['spesifikasi'] : '') ?></td>
                <td class="text-center"><?= $p['jumlah'] ?></td>
                <td class="text-center"><?= htmlspecialchars($p['satuan']) ?></td>
                <td class="text-end"><?= number_format($p['harga_satuan'], 0, ',', '.') ?></td>
                <td class="text-end"><?= number_format($subtotal, 0, ',', '.') ?></td>
                <td></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="table-secondary">
                <th colspan="11" class="text-end">TOTAL KESELURUHAN</th>
                <th class="text-end fw-bold"><?= number_format($total_seluruh, 0, ',', '.') ?></th>
                <th></th>
            </tr>
        </tfoot>
    </table>
    
    <?= render_ttd($pdo, 'pengeluaran') ?>
    </div>

<?php elseif ($jenis_laporan == 'penyaluran'): 
    $penyaluran = $pdo->query("SELECT t.tanggal as tgl_transaksi, t.bukti, t.jumlah, t.keterangan, 
        b.kode_barang, b.nama_barang, b.spesifikasi, b.harga_satuan, b.satuan,
        dp.tgl_sppb, dp.no_sppb, dp.tgl_bast, dp.no_bast, dp.pemohon
        FROM transaksi t 
        JOIN barang b ON t.barang_id = b.id 
        LEFT JOIN dokumen_pengeluaran dp ON t.bukti = dp.bukti_transaksi 
        WHERE t.jenis = 'Keluar' ORDER BY t.tanggal ASC")->fetchAll(PDO::FETCH_ASSOC);
    $total_seluruh = 0;
?>
    <div class="doc-page doc-landscape">
    <h4 class="text-center mb-4">BUKU PENYALURAN PERSEDIAAN<br>BULAN <?= strtoupper(date('F Y')) ?></h4>
    
    <table class="table table-bordered table-sm mt-3">
        <thead class="table-light text-center align-middle">
            <tr>
                <th rowspan="2" width="3%">No.</th>
                <th colspan="2">BAST</th>
                <th rowspan="2" width="8%">KODE BARANG</th>
                <th rowspan="2" width="15%">Nama Barang</th>
                <th colspan="2">SPESIFIKASI BARANG</th>
                <th rowspan="2" width="5%">JUM<br>LAH</th>
                <th rowspan="2" width="8%">Harga<br>Satuan (Rp)</th>
                <th rowspan="2" width="8%">Nilai Total (Rp)</th>
                <th colspan="2">SPPB</th>
                <th rowspan="2" width="10%">PENERIMA</th>
                <th rowspan="2" width="8%">KETERANGAN</th>
            </tr>
            <tr>
                <th width="8%">TANGGAL</th>
                <th width="10%">NOMOR</th>
                <th width="5%">NUSP</th>
                <th width="15%">SPESIFIKASI NAMA<br>BARANG</th>
                <th width="8%">TANGGAL</th>
                <th width="10%">NOMOR</th>
            </tr>
        </thead>
        <tbody>
            <?php $no=1; foreach($penyaluran as $p): 
                $subtotal = $p['jumlah'] * $p['harga_satuan'];
                $total_seluruh += $subtotal;
                $tgl_bast = !empty($p['tgl_bast']) ? $p['tgl_bast'] : $p['tgl_transaksi'];
                $no_bast = !empty($p['no_bast']) ? $p['no_bast'] : $p['bukti'];
                $tgl_sppb = !empty($p['tgl_sppb']) ? $p['tgl_sppb'] : $p['tgl_transaksi'];
                $no_sppb = !empty($p['no_sppb']) ? $p['no_sppb'] : $p['bukti'];
                $penerima = !empty($p['pemohon']) ? $p['pemohon'] : $p['keterangan'];
            ?>
            <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td><?= $tgl_bast ?></td>
                <td><?= htmlspecialchars($no_bast) ?></td>
                <td class="text-center"><?= htmlspecialchars(isset($p['kode_barang']) ? $p['kode_barang'] : '') ?></td>
                <td><?= htmlspecialchars($p['nama_barang']) ?></td>
                <td class="text-center">-</td>
                <td><?= htmlspecialchars(isset($p['spesifikasi']) ? $p['spesifikasi'] : '') ?></td>
                <td class="text-center"><?= $p['jumlah'] ?></td>
                <td class="text-end"><?= number_format($p['harga_satuan'], 0, ',', '.') ?></td>
                <td class="text-end"><?= number_format($subtotal, 0, ',', '.') ?></td>
                <td><?= $tgl_sppb ?></td>
                <td><?= htmlspecialchars($no_sppb) ?></td>
                <td><?= htmlspecialchars($penerima) ?></td>
                <td></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="table-secondary">
                <th colspan="9" class="text-end">TOTAL KESELURUHAN</th>
                <th class="text-end fw-bold"><?= number_format($total_seluruh, 0, ',', '.') ?></th>
                <th colspan="4"></th>
            </tr>
        </tfoot>
    </table>
    
    <?= render_ttd($pdo, 'penyaluran') ?>
    </div>

<?php elseif ($jenis_laporan == 'opname'): 
    $nomor_opname = isset($_GET['nomor']) ? $_GET['nomor'] : '';
    $tanggal_opname = isset($_GET['tanggal']) ? $_GET['tanggal'] : '';
    
    if (empty($nomor_opname) || empty($tanggal_opname)):
?>
    <div class="card w-100 mx-auto mt-4 border-dark d-print-none" style="max-width: 600px;">
        <div class="card-header bg-primary text-white fw-bold">Lengkapi Data Stock Opname</div>
        <div class="card-body">
            <form method="get">
                <input type="hidden" name="jenis" value="opname">
                <div class="mb-3">
                    <label>Nomor Surat (Berita Acara)</label>
                    <input type="text" name="nomor" class="form-control" required placeholder="Contoh: 001/AR.03.05/BAST-INV/III/2025">
                </div>
                <div class="mb-3">
                    <label>Tanggal Pelaksanaan</label>
                    <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Cetak Dokumen</button>
            </form>
        </div>
    </div>
<?php else:
    $master = $pdo->query("SELECT * FROM barang ORDER BY nama_barang ASC")->fetchAll(PDO::FETCH_ASSOC);
    $total_seluruh = 0;
    
    $days = ['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'];
    $months = ['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'];
    
    $ts = strtotime($tanggal_opname);
    $hari = $days[date('l', $ts)];
    $tgl_num = date('d', $ts);
    $bln_str = $months[date('m', $ts)];
    $thn_num = date('Y', $ts);
    
    $tgl_terbilang = terbilang((int)$tgl_num);
    $thn_terbilang = terbilang((int)$thn_num);
?>
    <div class="doc-page doc-portrait">
    <?= $kop_html ?>
    <h4 class="text-center text-decoration-underline mb-0">BERITA ACARA INVENTARISASI FISIK PERSEDIAAN (STOCK OPNAME)</h4>
    <p class="text-center mb-4">Nomor : <?= htmlspecialchars($nomor_opname) ?></p>
    
    <p>Pada hari ini <strong><?= $hari ?></strong> tanggal <strong><?= $tgl_terbilang ?></strong> bulan <strong><?= $bln_str ?></strong> tahun <strong><?= $thn_terbilang ?></strong> bertempat di <?= htmlspecialchars(isset($kop['kop_3']) ? $kop['kop_3'] : '') ?>, yang bertanda tangan dibawah ini :</p>
    
    <?php 
    $stmt_opname = $pdo->prepare("SELECT t.*, p.nama, p.nip FROM pengaturan_ttd t LEFT JOIN ref_pejabat p ON t.pejabat_id = p.id WHERE t.jenis_laporan = 'opname' ORDER BY t.urutan ASC");
    $stmt_opname->execute();
    $ttd_opname = $stmt_opname->fetchAll(PDO::FETCH_ASSOC);
    if (count($ttd_opname) > 0):
        $no_ttd = 1;
        foreach($ttd_opname as $t):
    ?>
    <div style="margin-left: 20px; margin-bottom: 15px;">
        <div style="float: left; width: 25px;"><?= $no_ttd++ ?></div>
        <div style="float: left; width: 100px;">Nama<br>NIP.<br>Pangkat/Gol<br>Jabatan</div>
        <div style="float: left;">: <strong><?= htmlspecialchars($t['nama']) ?></strong><br>: <?= htmlspecialchars($t['nip']) ?><br>: -<br>: <?= htmlspecialchars($t['jabatan']) ?></div>
        <div style="clear: both;"></div>
    </div>
    <?php 
        endforeach;
    else: 
    ?>
    <div class="alert alert-warning d-print-none">Pengaturan Penandatangan (Pejabat) untuk jenis laporan "opname" belum diatur di Master Pejabat.</div>
    <?php endif; ?>
    
    <p>Telah melakukan pemeriksaan fisik berupa barang persediaan pada unit <?= htmlspecialchars(isset($kop['kop_3']) ? $kop['kop_3'] : '') ?> sebagai berikut dengan :</p>

    <table class="table table-bordered table-sm mt-3">
        <thead class="table-light text-center align-middle">
            <tr>
                <th width="5%">No.</th>
                <th width="20%">Kode Barang</th>
                <th width="40%">Nama Barang / Spesifikasi</th>
                <th width="10%">Jumlah</th>
                <th width="15%">Nilai (Rp)</th>
                <th width="10%">Ket.</th>
            </tr>
        </thead>
        <tbody>
            <?php $no=1; foreach($master as $b): 
                $stok = get_stok_akhir($pdo, $b['id'], $b['stok_awal']);
                if ($stok <= 0) continue; // Only show items with stock > 0 for opname (or change to show all)
                $nilai = $stok * $b['harga_satuan'];
                $total_seluruh += $nilai;
            ?>
            <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td class="text-center"><?= htmlspecialchars($b['kode_barang']) ?></td>
                <td><?= htmlspecialchars($b['nama_barang']) ?></td>
                <td class="text-center"><?= $stok ?></td>
                <td class="text-end"><?= number_format($nilai, 0, ',', '.') ?></td>
                <td></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="table-secondary">
                <th colspan="4" class="text-end">TOTAL NILAI</th>
                <th class="text-end fw-bold"><?= number_format($total_seluruh, 0, ',', '.') ?></th>
                <th></th>
            </tr>
        </tfoot>
    </table>
    
    <?= render_ttd($pdo, 'opname') ?>
    </div>
<?php endif; ?>

<?php elseif ($jenis_laporan == 'mutasi'): 
    $master = $pdo->query("SELECT * FROM barang ORDER BY nama_barang ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
    <?= $kop_html ?>
    <h4 class="text-center">DAFTAR MUTASI BARANG HABIS PAKAI</h4>
    <div class="table-responsive">
    <table class="table table-bordered table-sm mt-3">
        <thead class="table-dark text-center align-middle">
            <tr>
                <th rowspan="2">No</th>
                <th rowspan="2">Nama Barang / Spesifikasi</th>
                <th rowspan="2">Satuan</th>
                <th rowspan="2">Harga Satuan (Rp)</th>
                <th colspan="4">Mutasi (Kuantitas)</th>
                <th rowspan="2">Total Nilai Persediaan Akhir (Rp)</th>
            </tr>
            <tr>
                <th>Stok Awal</th>
                <th>Masuk</th>
                <th>Keluar</th>
                <th>Stok Akhir</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no=1; 
            $grand_total = 0;
            foreach($master as $b): 
                $s = $pdo->prepare("SELECT SUM(CASE WHEN jenis = 'Masuk' THEN jumlah ELSE 0 END) as t_masuk, SUM(CASE WHEN jenis = 'Keluar' THEN jumlah ELSE 0 END) as t_keluar FROM transaksi WHERE barang_id = ?");
                $s->execute([$b['id']]);
                $trx = $s->fetch(PDO::FETCH_ASSOC);
                
                $masuk = $trx['t_masuk'] ? $trx['t_masuk'] : 0;
                $keluar = $trx['t_keluar'] ? $trx['t_keluar'] : 0;
                $stok_akhir = get_stok_akhir($pdo, $b['id'], $b['stok_awal']);
                $nilai_akhir = $stok_akhir * $b['harga_satuan'];
                $grand_total += $nilai_akhir;
            ?>
            <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td><?= htmlspecialchars($b['nama_barang']) ?></td>
                <td class="text-center"><?= htmlspecialchars($b['satuan']) ?></td>
                <td class="text-end"><?= number_format($b['harga_satuan'], 0, ',', '.') ?></td>
                <td class="text-center"><?= $b['stok_awal'] ?></td>
                <td class="text-center"><?= $masuk ?></td>
                <td class="text-center"><?= $keluar ?></td>
                <td class="text-center fw-bold"><?= $stok_akhir ?></td>
                <td class="text-end fw-bold"><?= number_format($nilai_akhir, 0, ',', '.') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="table-secondary">
                <th colspan="8" class="text-end">TOTAL NILAI SELURUH PERSEDIAAN</th>
                <th class="text-end fw-bold fs-5">Rp <?= number_format($grand_total, 0, ',', '.') ?></th>
            </tr>
        </tfoot>
    </table>
    </div>
    
    <?= render_ttd($pdo, 'mutasi') ?>

<?php elseif ($jenis_laporan == 'bast' && $trx_bukti): 
    $s = $pdo->prepare("SELECT t.*, b.nama_barang, b.satuan, b.harga_satuan FROM transaksi t JOIN barang b ON t.barang_id = b.id WHERE t.bukti = ?");
    $s->execute([$trx_bukti]);
    $items = $s->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($items) > 0):
        $tr = $items[0]; // Header info (tanggal, jenis, bukti, keterangan)
        
        if ($tr['jenis'] == 'Masuk'):
            // Check if form submitted
            if (!isset($_GET['no_sk'])):
?>
    <div class="card w-100 mx-auto mt-4 border-dark d-print-none" style="max-width: 600px;">
        <div class="card-header bg-primary text-white fw-bold">Lengkapi Data BAST Penerimaan</div>
        <div class="card-body">
            <form method="get">
                <input type="hidden" name="jenis" value="bast">
                <input type="hidden" name="trx_bukti" value="<?= htmlspecialchars($trx_bukti) ?>">
                <div class="mb-3">
                    <label>Nomor Surat Keputusan (SK)</label>
                    <input type="text" name="no_sk" class="form-control" required placeholder="Contoh: 800/005 a/CADISDIK-WIL.X/Kpts/2025">
                </div>
                <div class="mb-3">
                    <label>Nomor BAST / INVOICE (Dari Penyedia)</label>
                    <input type="text" name="no_invoice" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Tanggal BAST / INVOICE</label>
                    <input type="date" name="tgl_invoice" class="form-control" value="<?= $tr['tanggal'] ?>" required>
                </div>
                <div class="mb-3">
                    <label>Nomor Surat Pesanan (PO)</label>
                    <input type="text" name="no_po" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Tanggal Surat Pesanan (PO)</label>
                    <input type="date" name="tgl_po" class="form-control" value="<?= $tr['tanggal'] ?>" required>
                </div>
                <div class="mb-3">
                    <label>Nama Penyedia Barang (CV/PT)</label>
                    <input type="text" name="penyedia" class="form-control" value="<?= htmlspecialchars($tr['keterangan']) ?>" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Lanjut Cetak</button>
            </form>
        </div>
    </div>
<?php else: 
    $days = ['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'];
    $months = ['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'];
    
    $ts = strtotime($tr['tanggal']);
    $hari = $days[date('l', $ts)];
    $tgl_num = date('d', $ts);
    $bln_str = $months[date('m', $ts)];
    $thn_num = date('Y', $ts);
    
    $tgl_terbilang = terbilang((int)$tgl_num);
    $thn_terbilang = terbilang((int)$thn_num);
    
    $ts_inv = strtotime($_GET['tgl_invoice']);
    $tgl_inv_fmt = date('j', $ts_inv) . ' ' . $months[date('m', $ts_inv)] . ' ' . date('Y', $ts_inv);
    
    $ts_po = strtotime($_GET['tgl_po']);
    $tgl_po_fmt = date('j', $ts_po) . ' ' . $months[date('m', $ts_po)] . ' ' . date('Y', $ts_po);
?>
    <div class="doc-page doc-portrait">
        <?= $kop_html ?>
        <h4 class="text-center text-decoration-underline mb-0">BERITA ACARA PEMERIKSAAN BARANG</h4>
        <p class="text-center mb-4">Nomor : <?= htmlspecialchars($tr['bukti']) ?></p>
        
        <p class="text-justify" style="text-align: justify;">Pada hari ini <strong><?= $hari ?></strong> tanggal <strong><?= $tgl_terbilang ?></strong> bulan <strong><?= $bln_str ?></strong> tahun <strong><?= $thn_terbilang ?></strong>, kami yang bertanda tangan dibawah ini Petugas/Tim Pemeriksaan Barang berdasarkan Surat Keputusan No. <?= htmlspecialchars($_GET['no_sk']) ?> menerangkan:</p>
        
        <?php 
        $stmt_bast = $pdo->prepare("SELECT t.*, p.nama, p.nip FROM pengaturan_ttd t LEFT JOIN ref_pejabat p ON t.pejabat_id = p.id WHERE t.jenis_laporan = 'bast_masuk' ORDER BY t.urutan ASC LIMIT 1");
        $stmt_bast->execute();
        $ttd_bast = $stmt_bast->fetch(PDO::FETCH_ASSOC);
        if ($ttd_bast):
        ?>
        <div style="margin-left: 20px; margin-bottom: 15px;">
            <div style="float: left; width: 100px;">Nama<br>NIP.<br>Jabatan</div>
            <div style="float: left;">: <strong><?= htmlspecialchars($ttd_bast['nama']) ?></strong><br>: <?= htmlspecialchars($ttd_bast['nip']) ?><br>: <?= htmlspecialchars($ttd_bast['jabatan']) ?></div>
            <div style="clear: both;"></div>
        </div>
        <?php else: ?>
        <div class="alert alert-warning d-print-none">Pengaturan Penandatangan (Pejabat) untuk jenis laporan "bast_masuk" belum diatur di Master Pejabat.</div>
        <?php endif; ?>
        
        <p class="text-justify" style="text-align: justify;">Dengan ini menyatakan bahwa berdasarkan BAST/INVOICE Nomor : <?= htmlspecialchars($_GET['no_invoice']) ?> tanggal <?= $tgl_inv_fmt ?> sebagai realisasi Surat Pesanan nomor <?= htmlspecialchars($_GET['no_po']) ?> tanggal <?= $tgl_po_fmt ?> yang dipercayakan kepada <?= htmlspecialchars($_GET['penyedia']) ?> selaku penyedia barang dengan rincian belanja sebagai berikut:</p>

        <table class="table table-bordered table-sm mb-4">
            <thead class="table-light text-center align-middle">
                <tr>
                    <th width="5%">No.</th>
                    <th width="65%">Nama Barang</th>
                    <th width="10%">Unit</th>
                    <th width="10%">Satuan</th>
                    <th width="10%">Ket.</th>
                </tr>
            </thead>
            <tbody>
                <?php $no=1; foreach($items as $i): ?>
                <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td><?= htmlspecialchars($i['nama_barang']) ?></td>
                    <td class="text-center"><?= $i['jumlah'] ?></td>
                    <td class="text-center"><?= htmlspecialchars($i['satuan']) ?></td>
                    <td></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <p>Demikian Berita Acara Pemeriksaan Barang ini dibuat untuk dipergunakan sebagaimana mestinya.</p>
        
        <div class="d-flex justify-content-end mt-5">
            <div class="text-center" style="width: 300px;">
                <p>Petugas/Tim Pemeriksa Barang</p>
                <br><br><br>
                <strong><u><?= $ttd_bast ? htmlspecialchars($ttd_bast['nama']) : '..................................' ?></u></strong><br>
                NIP. <?= $ttd_bast ? htmlspecialchars($ttd_bast['nip']) : '..................................' ?>
            </div>
        </div>
    </div>
<?php endif; ?>
<?php endif; ?>
<?php endif; ?>

<?php elseif ($is_dokumen_keluar && $trx_bukti): 
    $s = $pdo->prepare("SELECT t.*, b.nama_barang, b.satuan, b.harga_satuan, b.kode_barang, b.spesifikasi, b.stok_awal FROM transaksi t JOIN barang b ON t.barang_id = b.id WHERE t.bukti = ?");
    $s->execute([$trx_bukti]);
    $items = $s->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($items) > 0):
        $tr = $items[0];
        
        $cek_doc = $pdo->prepare("SELECT * FROM dokumen_pengeluaran WHERE bukti_transaksi = ?");
        $cek_doc->execute([$trx_bukti]);
        $doc = $cek_doc->fetch(PDO::FETCH_ASSOC);

        if (!$doc):
?>
    <div class="card w-100 mx-auto mt-4 border-dark d-print-none" style="max-width: 800px;">
        <div class="card-header bg-warning fw-bold">Nomor Dokumen Belum Lengkap</div>
        <div class="card-body">
            <p>Transaksi ini belum memiliki nomor surat NPB, SPB, SPPB, dan BAST. Silakan lengkapi form berikut untuk melanjutkan pencetakan dokumen pengeluaran.</p>
            <form method="post">
                <input type="hidden" name="action" value="save_dokumen">
                <input type="hidden" name="bukti_transaksi" value="<?= htmlspecialchars($trx_bukti) ?>">
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Pihak / Unit Pemohon (Pihak Kedua)</label>
                        <input type="text" name="pemohon" class="form-control" value="<?= htmlspecialchars($tr['keterangan']) ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Pihak Pertama (Pengurus Barang)</label>
                        <select name="pihak_pertama_id" class="form-select" required>
                            <option value="">-- Pilih Pejabat --</option>
                            <?php 
                            $pejabat_list = $pdo->query("SELECT * FROM ref_pejabat ORDER BY nama")->fetchAll(PDO::FETCH_ASSOC);
                            foreach($pejabat_list as $pj): 
                            ?>
                            <option value="<?= $pj['id'] ?>"><?= htmlspecialchars($pj['nama']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Nomor NPB (Nota Permintaan)</label>
                        <input type="text" name="no_npb" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Tanggal NPB</label>
                        <input type="date" name="tgl_npb" class="form-control" value="<?= $tr['tanggal'] ?>" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label>Nomor SPB (Surat Permintaan)</label>
                        <input type="text" name="no_spb" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Tanggal SPB</label>
                        <input type="date" name="tgl_spb" class="form-control" value="<?= $tr['tanggal'] ?>" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label>Nomor SPPB (Surat Perintah)</label>
                        <input type="text" name="no_sppb" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Tanggal SPPB</label>
                        <input type="date" name="tgl_sppb" class="form-control" value="<?= $tr['tanggal'] ?>" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label>Nomor BAST (Berita Acara)</label>
                        <input type="text" name="no_bast" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Tanggal BAST</label>
                        <input type="date" name="tgl_bast" class="form-control" value="<?= $tr['tanggal'] ?>" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary w-100 mt-3">Simpan & Cetak Dokumen</button>
            </form>
        </div>
    </div>
<?php else: 
    // Fetch pihak_pertama details
    $pihak1_nama = '';
    $pihak1_nip = '';
    if (!empty($doc['pihak_pertama_id'])) {
        $st_p1 = $pdo->prepare("SELECT * FROM ref_pejabat WHERE id = ?");
        $st_p1->execute([$doc['pihak_pertama_id']]);
        $p1 = $st_p1->fetch(PDO::FETCH_ASSOC);
        if ($p1) {
            $pihak1_nama = $p1['nama'];
            $pihak1_nip = $p1['nip'];
        }
    }
    $doc['pihak1_nama'] = $pihak1_nama;
    $doc['pihak1_nip'] = $pihak1_nip;

    // Parse templates
    function parse_template($tpl, $doc) {
        $tpl = str_replace('{no_npb}', $doc['no_npb'], $tpl);
        $tpl = str_replace('{tgl_npb}', $doc['tgl_npb'], $tpl);
        $tpl = str_replace('{no_spb}', $doc['no_spb'], $tpl);
        $tpl = str_replace('{tgl_spb}', $doc['tgl_spb'], $tpl);
        $tpl = str_replace('{no_sppb}', $doc['no_sppb'], $tpl);
        $tpl = str_replace('{tgl_sppb}', $doc['tgl_sppb'], $tpl);
        $tpl = str_replace('{no_bast}', $doc['no_bast'], $tpl);
        $tpl = str_replace('{tgl_bast}', $doc['tgl_bast'], $tpl);
        $tpl = str_replace('{pemohon}', $doc['pemohon'], $tpl);
        $tpl = str_replace('{pihak1_nama}', isset($doc['pihak1_nama']) ? $doc['pihak1_nama'] : '', $tpl);
        $tpl = str_replace('{pihak1_nip}', isset($doc['pihak1_nip']) ? $doc['pihak1_nip'] : '', $tpl);
        
        $tpl = htmlspecialchars($tpl);
        $tpl = str_replace('\n', '<br>', $tpl);
        $tpl = str_replace('\r', '', $tpl);
        return nl2br($tpl);
    }
    
    $teks_spb = parse_template(isset($kop['teks_spb_dasar']) ? $kop['teks_spb_dasar'] : '', $doc);
    $teks_sppb = parse_template(isset($kop['teks_sppb_dasar']) ? $kop['teks_sppb_dasar'] : '', $doc);
    $teks_bast_buka = parse_template(isset($kop['teks_bast_pembuka']) ? $kop['teks_bast_pembuka'] : '', $doc);
    $teks_bast_tutup = parse_template(isset($kop['teks_bast_penutup']) ? $kop['teks_bast_penutup'] : '', $doc);
    if ($jenis_laporan == 'npb') $cetak = 'npb';
    elseif ($jenis_laporan == 'spb') $cetak = 'spb';
    elseif ($jenis_laporan == 'sppb') $cetak = 'sppb';
    elseif ($jenis_laporan == 'bast_keluar') $cetak = 'bast';
    else $cetak = isset($_GET['cetak']) ? $_GET['cetak'] : 'semua';
?>

    <?php if ($cetak == 'semua' || $cetak == 'npb'): ?>
    <!-- P1: NPB (Portrait) -->
    <div class="doc-page doc-portrait">
        <?= $kop_html ?>
        <h4 class="text-center text-decoration-underline mb-0">NOTA PERMINTAAN BARANG</h4>
        <p class="text-center mb-4">Nomor : <?= htmlspecialchars($doc['no_npb']) ?></p>
        <p>Pihak Yang Meminta : <?= htmlspecialchars($doc['pemohon']) ?></p>
        
        <table class="table table-bordered table-sm mb-4">
            <thead class="table-light text-center align-middle">
                <tr>
                    <th width="5%">No</th>
                    <th width="40%">SPESIFIKASI NAMA BARANG</th>
                    <th width="10%">JUMLAH</th>
                    <th width="15%">SATUAN BARANG</th>
                    <th width="15%">KEPERLUAN</th>
                    <th width="15%">KETERANGAN</th>
                </tr>
            </thead>
            <tbody>
                <?php $no=1; foreach($items as $i): ?>
                <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td>
                        <?= htmlspecialchars($i['nama_barang']) ?>
                        <?php if(!empty($i['spesifikasi'])): ?><br><small><?= htmlspecialchars($i['spesifikasi']) ?></small><?php endif; ?>
                    </td>
                    <td class="text-center"><?= $i['jumlah'] ?></td>
                    <td class="text-center"><?= htmlspecialchars($i['satuan']) ?></td>
                    <td>Kebutuhan kegiatan rutin sekolah</td>
                    <td></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?= render_ttd($pdo, 'pengeluaran', $doc['pemohon']) ?>
    </div>
    <?php endif; ?>

    <?php if ($cetak == 'semua' || $cetak == 'spb'): ?>
    <!-- P2: SPB (Landscape) -->
    <div class="doc-page doc-landscape">
        <h4 class="text-center text-decoration-underline mb-0">SURAT PERMINTAAN BARANG (SPB)</h4>
        <p class="text-center mb-4">Nomor : <?= htmlspecialchars($doc['no_spb']) ?></p>
        
        <div class="mb-3">
            <?= $teks_spb ?>
        </div>
        
        <table class="table table-bordered table-sm mb-4">
            <thead class="table-light text-center align-middle">
                <tr>
                    <th rowspan="2" width="3%">No</th>
                    <th rowspan="2" width="10%">Kode Barang</th>
                    <th rowspan="2" width="15%">Nama Barang</th>
                    <th rowspan="2" width="5%">NUSP</th>
                    <th rowspan="2" width="20%">Spesifikasi Nama Barang</th>
                    <th colspan="2">Pengajuan Permintaan</th>
                    <th colspan="2">Informasi Sisa Barang Persediaan</th>
                    <th colspan="2">Usulan Persetujuan</th>
                    <th rowspan="2" width="10%">Keperluan</th>
                    <th rowspan="2" width="5%">Ket</th>
                </tr>
                <tr>
                    <th>Jml<br>Brg</th>
                    <th>Satuan<br>Barang</th>
                    <th>Jml<br>Brg</th>
                    <th>Satuan<br>Barang</th>
                    <th>Jml<br>Brg</th>
                    <th>Satuan<br>Barang</th>
                </tr>
            </thead>
            <tbody>
                <?php $no=1; foreach($items as $i): 
                    $stok_sisa = get_stok_akhir($pdo, $i['barang_id'], $i['stok_awal']);
                ?>
                <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td class="text-center"><?= htmlspecialchars(isset($i['kode_barang']) ? $i['kode_barang'] : '') ?></td>
                    <td><?= htmlspecialchars($i['nama_barang']) ?></td>
                    <td class="text-center">-</td>
                    <td><?= htmlspecialchars(isset($i['spesifikasi']) ? $i['spesifikasi'] : '') ?></td>
                    <td class="text-center"><?= $i['jumlah'] ?></td>
                    <td class="text-center"><?= htmlspecialchars($i['satuan']) ?></td>
                    <td class="text-center"><?= $stok_sisa ?></td>
                    <td class="text-center"><?= htmlspecialchars($i['satuan']) ?></td>
                    <td class="text-center"><?= $i['jumlah'] ?></td>
                    <td class="text-center"><?= htmlspecialchars($i['satuan']) ?></td>
                    <td>Operasional Sekolah</td>
                    <td></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?= render_ttd($pdo, 'bast_keluar', $doc['pemohon']) ?>
    </div>
    <?php endif; ?>

    <?php if ($cetak == 'semua' || $cetak == 'sppb'): ?>
    <!-- P3: SPPB (Portrait) -->
    <div class="doc-page doc-portrait">
        <?= $kop_html ?>
        <h4 class="text-center text-decoration-underline mb-0">SURAT PERINTAH PENYALURAN BARANG (SPPB)</h4>
        <p class="text-center mb-4">Nomor : <?= htmlspecialchars($doc['no_sppb']) ?></p>
        
        <div class="mb-3">
            <?= $teks_sppb ?>
        </div>
        
        <table class="table table-bordered table-sm mb-4">
            <thead class="table-light text-center align-middle">
                <tr>
                    <th rowspan="2" width="5%">No</th>
                    <th rowspan="2" width="15%">Kode Barang</th>
                    <th rowspan="2" width="20%">Nama Barang</th>
                    <th rowspan="2" width="25%">Spesifikasi Nama Barang</th>
                    <th colspan="2">Persetujuan</th>
                    <th rowspan="2" width="10%">Ket</th>
                </tr>
                <tr>
                    <th>Jumlah</th>
                    <th>Satuan Barang</th>
                </tr>
            </thead>
            <tbody>
                <?php $no=1; foreach($items as $i): ?>
                <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td class="text-center"><?= htmlspecialchars(isset($i['kode_barang']) ? $i['kode_barang'] : '') ?></td>
                    <td><?= htmlspecialchars($i['nama_barang']) ?></td>
                    <td><?= htmlspecialchars(isset($i['spesifikasi']) ? $i['spesifikasi'] : '') ?></td>
                    <td class="text-center"><?= $i['jumlah'] ?></td>
                    <td class="text-center"><?= htmlspecialchars($i['satuan']) ?></td>
                    <td></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?= render_ttd($pdo, 'bast_keluar', $doc['pemohon']) ?>
    </div>
    <?php endif; ?>

    <?php if ($cetak == 'semua' || $cetak == 'bast'): ?>
    <!-- P4: BAST (Portrait) -->
    <div class="doc-page doc-portrait">
        <?= $kop_html ?>
        <h4 class="text-center mb-0">BERITA ACARA SERAH TERIMA (BAST) PENYALURAN BARANG PERSEDIAAN</h4>
        <p class="text-center mb-4">Nomor : <?= htmlspecialchars($doc['no_bast']) ?></p>
        
        <div class="mb-3">
            <?= $teks_bast_buka ?>
        </div>
        
        <table class="table table-bordered table-sm mb-4">
            <thead class="table-light text-center align-middle">
                <tr>
                    <th width="5%">No</th>
                    <th width="15%">Kode Barang</th>
                    <th width="20%">Nama Barang</th>
                    <th width="25%">Spesifikasi Nama Barang</th>
                    <th width="10%">Jumlah</th>
                    <th width="15%">Satuan Barang</th>
                    <th width="10%">Ket</th>
                </tr>
            </thead>
            <tbody>
                <?php $no=1; foreach($items as $i): ?>
                <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td class="text-center"><?= htmlspecialchars(isset($i['kode_barang']) ? $i['kode_barang'] : '') ?></td>
                    <td><?= htmlspecialchars($i['nama_barang']) ?></td>
                    <td><?= htmlspecialchars(isset($i['spesifikasi']) ? $i['spesifikasi'] : '') ?></td>
                    <td class="text-center"><?= $i['jumlah'] ?></td>
                    <td class="text-center"><?= htmlspecialchars($i['satuan']) ?></td>
                    <td></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="mb-3">
            <?= $teks_bast_tutup ?>
        </div>
        
        <?= render_ttd($pdo, 'bast_keluar', $doc['pemohon']) ?>
    </div>
    <?php endif; ?>
<?php endif; ?>
<?php endif; ?>
<?php endif; ?>
</div>
</div>

<style>
    body { background-color: #f0f2f5; }
    .table { font-size: 10pt; }
    @media print {
        @page {
            size: A4 portrait;
            margin-top: 0.5cm;
            margin-bottom: 1cm;
            margin-left: 1cm;
            margin-right: 1cm;
        }
        @page landscape_page {
            size: A4 landscape;
            margin-top: 0.5cm;
            margin-bottom: 1cm;
            margin-left: 1cm;
            margin-right: 1cm;
        }
        .doc-landscape { page: landscape_page; }
        .doc-page { page-break-after: always; }
        .doc-page:last-child { page-break-after: auto; }
        html, body {
            padding: 0 !important;
            margin: 0 !important;
            background-color: white !important;
        }
        .container-fluid, main {
            display: block !important;
            padding: 0 !important;
            margin: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
        }
        /* Reset row to prevent horizontal bleeding */
        .row { margin-left: 0 !important; margin-right: 0 !important; }
        
        .card, .card-body {
            box-shadow: none !important;
            border: none !important;
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
        }
        
        /* Force table to print solid black text without dark backgrounds */
        .table { width: 99.5% !important; margin: 0 auto !important; border-color: #000 !important; color: #000 !important; }
        .table th, .table td { border-color: #000 !important; color: #000 !important; }
        .table-dark, .table-secondary, thead, tfoot { background-color: transparent !important; color: #000 !important; }
        .table-dark th { color: #000 !important; font-weight: bold; }
        tfoot { display: table-row-group; }
        
        /* Must be at the very bottom to override anything else */
        .sidebar, .navbar, .d-print-none, .d-print-none * { 
            display: none !important; 
        }
    }
</style>
<?php if($jenis_laporan == 'bast' && $trx_bukti): ?>
<div class="text-center mt-3 d-print-none">
    <button class="btn btn-secondary" onclick="window.print()"><i class="bi bi-printer"></i> Cetak Dokumen</button>
</div>
<?php endif; ?>

<?php include 'layout_footer.php'; ?>
