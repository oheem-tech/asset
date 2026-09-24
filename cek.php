<?php
require 'config.php';
$pdo->exec("CREATE TABLE IF NOT EXISTS dokumen_penerimaan (
    bukti_transaksi TEXT PRIMARY KEY,
    no_sk TEXT,
    no_invoice TEXT,
    tgl_invoice TEXT,
    no_po TEXT,
    tgl_po TEXT,
    penyedia TEXT
)");
echo "Table created";
?>
