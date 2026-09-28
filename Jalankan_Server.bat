@echo off
cd /d "%~dp0"
color 0A
title Server Aplikasi Inventaris Asset
echo ========================================================
echo       MESIN SERVER APLIKASI SEDANG BERJALAN...
echo   JANGAN TUTUP JENDELA INI SELAMA APLIKASI DIGUNAKAN!
echo ========================================================
echo.

if exist "php\php.exe" (
    set PHP_EXE=php\php.exe
) else (
    set PHP_EXE=php
)

echo.
echo ========================================================
echo Tekan CTRL + C pada keyboard jika ingin mematikan server.
echo ========================================================
echo.

%PHP_EXE% -S localhost:8123
