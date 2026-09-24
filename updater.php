<?php
require_once 'config.php';

// ==========================================
// KONFIGURASI GITHUB UPDATER
// ==========================================
$github_username = 'oheem-tech'; // Ganti dengan username github anda
$github_repo     = 'asset';       // Ganti dengan nama repository
$branch          = 'main';                 // Branch utama (biasanya main atau master)
// ==========================================

$local_version_file = __DIR__ . '/version.txt';
if (!file_exists($local_version_file)) {
    file_put_contents($local_version_file, '1.0.0');
}
$local_version = trim(file_get_contents($local_version_file));

$remote_version = '';
$update_available = false;
$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'check_update') {
        // Cek versi terbaru dari raw github
        $url = "https://raw.githubusercontent.com/$github_username/$github_repo/$branch/version.txt";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'PHP Updater');
        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code == 200 && !empty($result)) {
            $remote_version = trim($result);
            if (version_compare($remote_version, $local_version, '>')) {
                $update_available = true;
                $msg = "Pembaruan tersedia! Versi terbaru adalah v$remote_version.";
            } else {
                $msg = "Anda sudah menggunakan versi terbaru (v$local_version).";
            }
        } else {
            $error = "Gagal mengecek pembaruan. Pastikan Username dan Repo di file updater.php sudah benar, atau periksa koneksi internet.";
        }
    } 
    elseif (isset($_POST['action']) && $_POST['action'] == 'do_update') {
        $zip_url = "https://github.com/$github_username/$github_repo/archive/refs/heads/$branch.zip";
        $zip_file = __DIR__ . '/update_temp.zip';
        
        // 1. Download ZIP
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $zip_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'PHP Updater');
        $zip_data = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code == 200 && !empty($zip_data)) {
            file_put_contents($zip_file, $zip_data);
            
            // 2. Extract ZIP
            $zip = new ZipArchive;
            if ($zip->open($zip_file) === TRUE) {
                $temp_dir = __DIR__ . '/temp_update_dir/';
                if (!is_dir($temp_dir)) mkdir($temp_dir);
                
                $zip->extractTo($temp_dir);
                $zip->close();
                
                // Struktur zip dari github biasanya: nama_repo-branch/file.php
                $extract_folder = $temp_dir . $github_repo . '-' . $branch . '/';
                
                if (is_dir($extract_folder)) {
                    // 3. Pindahkan file menimpa file lama (Kecuali database)
                    $iterator = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($extract_folder, RecursiveDirectoryIterator::SKIP_DOTS),
                        RecursiveIteratorIterator::SELF_FIRST
                    );
                    
                    foreach ($iterator as $item) {
                        $dest = __DIR__ . DIRECTORY_SEPARATOR . $iterator->getSubPathname();
                        
                        // PROTEKSI DATABASE (Jangan timpa folder db atau isinya)
                        if (strpos($iterator->getSubPathname(), 'db' . DIRECTORY_SEPARATOR) === 0 || $iterator->getSubPathname() === 'db') {
                            continue; 
                        }
                        // PROTEKSI FILE TERTENTU BILA PERLU
                        if ($iterator->getSubPathname() == 'config.php') {
                            // Opsional: biarkan config.php lama atau izinkan jika ada fitur baru
                            // Saat ini kita izinkan timpa karena config.php ini standar SQLite statis
                        }
                        
                        if ($item->isDir()) {
                            if (!is_dir($dest)) mkdir($dest);
                        } else {
                            copy($item, $dest);
                        }
                    }
                    $msg = "Update berhasil diinstal! Aplikasi sekarang menggunakan versi terbaru.";
                    // Update versi lokal agar sesuai
                    if (file_exists($extract_folder . 'version.txt')) {
                        copy($extract_folder . 'version.txt', $local_version_file);
                    }
                } else {
                    $error = "Struktur folder ZIP tidak dikenali. Gagal mengekstrak.";
                }
                
                // Bersihkan file temporer
                unlink($zip_file);
                // Fungsi hapus folder rekursif
                function deleteDir($dirPath) {
                    if (!is_dir($dirPath)) return;
                    if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') $dirPath .= '/';
                    $files = glob($dirPath . '*', GLOB_MARK);
                    foreach ($files as $file) {
                        if (is_dir($file)) deleteDir($file);
                        else unlink($file);
                    }
                    rmdir($dirPath);
                }
                deleteDir($temp_dir);
                
                // Refresh local version
                $local_version = trim(file_get_contents($local_version_file));
            } else {
                $error = "Gagal membuka file update ZIP.";
            }
        } else {
            $error = "Gagal mengunduh file update dari GitHub.";
        }
    }
}

include 'layout_header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2">Pembaruan Sistem (Auto-Updater)</h1>
</div>

<?php if($error): ?>
<div class="alert alert-danger fw-bold"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if($msg): ?>
<div class="alert alert-success fw-bold"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-6">
        <div class="card mb-4 border-primary">
            <div class="card-header bg-primary text-white fw-bold">
                <i class="bi bi-cloud-arrow-down-fill"></i> Cek Pembaruan Aplikasi
            </div>
            <div class="card-body">
                <h5 class="card-title">Versi Saat Ini: <span class="badge bg-secondary">v<?= htmlspecialchars($local_version) ?></span></h5>
                
                <?php if ($remote_version && $update_available): ?>
                    <h5 class="card-title text-success mt-3">Versi Tersedia: <span class="badge bg-success">v<?= htmlspecialchars($remote_version) ?></span></h5>
                    <p class="mt-2 text-muted">Update terbaru siap diunduh dan dipasang secara otomatis.</p>
                    <form method="post" onsubmit="return confirm('Apakah Anda yakin ingin memulai proses pembaruan? Proses ini mungkin memakan waktu beberapa detik. Data database Anda (folder db) aman dan tidak akan tertimpa.');">
                        <input type="hidden" name="action" value="do_update">
                        <button type="submit" class="btn btn-success btn-lg mt-2 w-100"><i class="bi bi-download"></i> Instal Pembaruan Sekarang</button>
                    </form>
                <?php else: ?>
                    <p class="mt-3 text-muted">Klik tombol di bawah ini untuk memeriksa apakah ada versi terbaru dari repositori GitHub aplikasi ini.</p>
                    <form method="post">
                        <input type="hidden" name="action" value="check_update">
                        <button type="submit" class="btn btn-primary mt-2 w-100"><i class="bi bi-search"></i> Cek Pembaruan</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card border-warning">
            <div class="card-header bg-warning text-dark fw-bold">
                <i class="bi bi-info-circle-fill"></i> Informasi Developer
            </div>
            <div class="card-body bg-light">
                <p>Fitur Auto-Updater ini akan mengunduh versi terbaru langsung dari GitHub. Sangat cocok digunakan jika Anda mendistribusikan aplikasi ini ke Client/Klien (seperti Sekolah atau Koperasi) agar mereka tidak perlu memanggil programmer setiap kali ada perbaikan *bug* atau fitur baru.</p>
                <hr>
                <h6>Langkah Konfigurasi untuk Developer:</h6>
                <ol class="small">
                    <li>Buka file <code>updater.php</code> menggunakan teks editor.</li>
                    <li>Ganti variabel <code>$github_username</code> dan <code>$github_repo</code> dengan nama akun dan repositori GitHub Anda.</li>
                    <li>Setiap kali Anda merilis pembaruan, jangan lupa untuk mengubah angka di dalam file <code>version.txt</code> pada repository GitHub Anda agar sistem klien bisa mendeteksinya.</li>
                    <li>Folder <code>db/</code> akan dilewati saat proses penimpaan (extract), sehingga data client aman!</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<?php include 'layout_footer.php'; ?>
