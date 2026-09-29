param([string]$AppDir)

$logFile = Join-Path $AppDir "install_log.txt"
$zipUrl  = "https://github.com/oheem-tech/asset/archive/refs/heads/main.zip"
$zipFile = Join-Path $AppDir "update_temp.zip"
$tempDir = Join-Path $AppDir "temp_update_dir"

function Log($msg) {
    $time = Get-Date -Format "HH:mm:ss"
    Add-Content -Path $logFile -Value "[$time] $msg"
}

if (Test-Path $logFile) { Remove-Item $logFile -Force }
Log "Memulai proses instalasi..."
Log "Folder tujuan: $AppDir"

try {
    Log "Mendownload dari: $zipUrl"
    $ProgressPreference = 'SilentlyContinue'
    [Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
    $wc = New-Object System.Net.WebClient
    $wc.Headers.Add("User-Agent", "Mozilla/5.0 Windows Installer")
    $wc.DownloadFile($zipUrl, $zipFile)
    Log "Download selesai. Ukuran: $(([System.IO.FileInfo]$zipFile).Length) bytes"
} catch {
    Log "GAGAL DOWNLOAD: $($_.Exception.Message)"
    exit 1
}

try {
    Log "Mengekstrak ZIP..."
    if (Test-Path $tempDir) { Remove-Item $tempDir -Recurse -Force }
    Expand-Archive -Path $zipFile -DestinationPath $tempDir -Force
    Log "Ekstraksi selesai."
} catch {
    Log "GAGAL EKSTRAK: $($_.Exception.Message)"
    exit 1
}

try {
    $sourceDir = Join-Path $tempDir "asset-main"
    if (-not (Test-Path $sourceDir)) {
        # Fallback: ambil subfolder pertama
        $sourceDir = (Get-ChildItem $tempDir -Directory | Select-Object -First 1).FullName
    }
    Log "Menyalin file dari: $sourceDir"

    Get-ChildItem -Path $sourceDir -Recurse | ForEach-Object {
        $relativePath = $_.FullName.Substring($sourceDir.Length + 1)
        # Lewati folder php dan database yang sudah ada
        if ($relativePath -like "php*") { return }
        if ($relativePath -eq "db\inventaris.sqlite" -and (Test-Path (Join-Path $AppDir "db\inventaris.sqlite"))) { return }

        $destPath = Join-Path $AppDir $relativePath
        if ($_.PSIsContainer) {
            if (-not (Test-Path $destPath)) { New-Item -ItemType Directory -Path $destPath -Force | Out-Null }
        } else {
            $destParent = Split-Path $destPath -Parent
            if (-not (Test-Path $destParent)) { New-Item -ItemType Directory -Path $destParent -Force | Out-Null }
            Copy-Item -Path $_.FullName -Destination $destPath -Force
        }
    }
    Log "Penyalinan file selesai."
} catch {
    Log "GAGAL SALIN: $($_.Exception.Message)"
    exit 1
}

# Bersihkan file sementara
if (Test-Path $zipFile) { Remove-Item $zipFile -Force }
if (Test-Path $tempDir) { Remove-Item $tempDir -Recurse -Force }

Log "SELESAI. Instalasi berhasil!"
