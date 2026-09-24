@echo off
color 0A
title Server Aplikasi Inventaris Asset
echo ========================================================
echo       MESIN SERVER APLIKASI SEDANG BERJALAN...
echo   JANGAN TUTUP JENDELA INI SELAMA APLIKASI DIGUNAKAN!
echo ========================================================
echo.
echo Aplikasi akan otomatis terbuka di Web Browser Anda...

:: Mengecek apakah ada folder php portable bawaan di dalam folder aplikasi
if exist "php\php.exe" (
    set PHP_EXE=php\php.exe
    echo Menggunakan PHP Portable Bawaan...
) else (
    :: Jika tidak ada (misal di komputer developer), gunakan PHP dari sistem XAMPP
    set PHP_EXE=php
    echo Menggunakan PHP dari Sistem Komputer...
)

:: Memberi waktu 2 detik agar server siap sebelum browser membuka halaman
timeout /t 2 /nobreak >nul

:: Membuka default web browser komputer klien ke alamat localhost:8123
start http://localhost:8123

echo.
echo ========================================================
echo Tekan CTRL + C pada keyboard jika ingin mematikan server.
echo ========================================================
echo.

:: Menjalankan built-in PHP server di port 8123
%PHP_EXE% -S localhost:8123
