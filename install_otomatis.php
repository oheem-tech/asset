<?php
// install_otomatis.php
// Skrip ini dijalankan oleh Inno Setup secara sembunyi-sembunyi saat proses instalasi berjalan

$github_username = 'oheem-tech'; // Ganti ini
$github_repo     = 'asset';       // Ganti ini
$branch          = 'main';

$zip_url = "https://github.com/$github_username/$github_repo/archive/refs/heads/$branch.zip";
$zip_file = __DIR__ . '/update_temp.zip';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $zip_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_USERAGENT, 'PHP Installer CLI');
$zip_data = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

if ($http_code == 200 && !empty($zip_data)) {
    file_put_contents($zip_file, $zip_data);
    $zip = new ZipArchive;
    if ($zip->open($zip_file) === TRUE) {
        $temp_dir = __DIR__ . '/temp_update_dir/';
        if (!is_dir($temp_dir)) mkdir($temp_dir);
        $zip->extractTo($temp_dir);
        $zip->close();
        
        $extract_folder = $temp_dir . $github_repo . '-' . $branch . '/';
        if (is_dir($extract_folder)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($extract_folder, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($iterator as $item) {
                $dest = __DIR__ . DIRECTORY_SEPARATOR . $iterator->getSubPathname();
                // Lewati folder php jika ada di repo (seharusnya tidak ada)
                if (strpos($iterator->getSubPathname(), 'php' . DIRECTORY_SEPARATOR) === 0) continue;
                
                if ($item->isDir()) {
                    if (!is_dir($dest)) mkdir($dest);
                } else {
                    copy($item, $dest);
                }
            }
        }
        
        unlink($zip_file);
        
        // Hapus folder temp
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
        file_put_contents(__DIR__ . '/install_log.txt', "SUKSES. Download & Ekstrak berhasil.\n");
    } else {
        file_put_contents(__DIR__ . '/install_log.txt', "GAGAL: Tidak bisa membuka file ZIP yang didownload.\n");
    }
} else {
    file_put_contents(__DIR__ . '/install_log.txt', "GAGAL DOWNLOAD dari GitHub.\nHTTP Code: $http_code\ncURL Error: $curl_error\nURL: $zip_url\n");
}
?>
