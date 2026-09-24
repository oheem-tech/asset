<?php
require_once 'config.php';
include 'layout_header.php';

// Get totals
$total_barang = $pdo->query("SELECT COUNT(*) FROM barang")->fetchColumn();
$total_transaksi = $pdo->query("SELECT COUNT(*) FROM transaksi")->fetchColumn();

// Get items with critical stock (e.g., stock < 10)
$stmt = $pdo->query("SELECT id, kode_barang, nama_barang, stok_awal FROM barang");
$barang_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

$kritis_count = 0;
$total_aset = 0;

foreach ($barang_list as $b) {
    $stok_akhir = get_stok_akhir($pdo, $b['id'], $b['stok_awal']);
    if ($stok_akhir < 10) {
        $kritis_count++;
    }
}

// Get recent transactions
$recent_trx = $pdo->query("
    SELECT t.tanggal, t.jenis, t.jumlah, b.nama_barang 
    FROM transaksi t 
    JOIN barang b ON t.barang_id = b.id 
    ORDER BY t.tanggal DESC, t.id DESC 
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2">Dashboard</h1>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <h5 class="card-title">Total Barang</h5>
                <h2 class="card-text"><?= htmlspecialchars($total_barang) ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card text-white bg-danger">
            <div class="card-body">
                <h5 class="card-title">Stok Kritis (< 10)</h5>
                <h2 class="card-text"><?= htmlspecialchars($kritis_count) ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card text-white bg-success">
            <div class="card-body">
                <h5 class="card-title">Total Transaksi</h5>
                <h2 class="card-text"><?= htmlspecialchars($total_transaksi) ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="mt-4">
    <h4>Transaksi Terakhir</h4>
    <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Jenis</th>
                <th>Nama Barang</th>
                <th>Jumlah</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($recent_trx)): ?>
            <tr><td colspan="4" class="text-center">Belum ada transaksi</td></tr>
            <?php else: ?>
                <?php foreach ($recent_trx as $trx): ?>
                <tr>
                    <td><?= htmlspecialchars($trx['tanggal']) ?></td>
                    <td>
                        <?php if($trx['jenis'] == 'Masuk'): ?>
                            <span class="badge bg-success">Masuk</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark">Keluar</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($trx['nama_barang']) ?></td>
                    <td><?= htmlspecialchars($trx['jumlah']) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'layout_footer.php'; ?>
