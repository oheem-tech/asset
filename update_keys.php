<?php
require 'config.php';
$pdo->exec("INSERT OR IGNORE INTO pengaturan (kunci, nilai) VALUES ('jenis_kop', 'teks')");
$pdo->exec("INSERT OR IGNORE INTO pengaturan (kunci, nilai) VALUES ('kop_banner', '')");
echo 'OK';
