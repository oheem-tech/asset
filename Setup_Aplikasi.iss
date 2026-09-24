; Inno Setup Script untuk Aplikasi Inventaris Aset
; Download Inno Setup Compiler dari: https://jrsoftware.org/isdl.php

[Setup]
AppName=Aplikasi Inventaris Aset
AppVersion=1.0
DefaultDirName=C:\Inventaris_Aset
DefaultGroupName=Inventaris Aset
OutputDir=.\Output
OutputBaseFilename=Setup_Inventaris
Compression=lzma2
SolidCompression=yes
; Agar database bisa ditulis/edit tanpa terblokir sistem Windows
PrivilegesRequired=lowest

[Files]
; HANYA membungkus mesin PHP dan skrip downloader. 
; File source code utama TIDAK dibungkus, melainkan didownload dari GitHub saat Klien menginstal.
Source: "php\*"; DestDir: "{app}\php"; Flags: ignoreversion recursesubdirs createallsubdirs
Source: "install_otomatis.php"; DestDir: "{app}"; Flags: ignoreversion

[Icons]
; Membuat Shortcut di Desktop
Name: "{userdesktop}\Inventaris Aset"; Filename: "{app}\Mulai_Aplikasi.bat"; IconFilename: "shell32.dll"; IconIndex: 130

; Membuat Shortcut Autorun/Startup agar jalan otomatis saat Windows menyala
Name: "{userstartup}\Inventaris Aset Server"; Filename: "{app}\Start_Hidden.vbs"; WorkingDir: "{app}"

[Run]
; 1. Menjalankan skrip downloader secara senyap saat instalasi (mengambil source code dari GitHub)
Filename: "{app}\php\php.exe"; Parameters: "install_otomatis.php"; Flags: runhidden waituntilterminated; StatusMsg: "Mengunduh file sistem terbaru dari server..."

; 2. Menjalankan aplikasi langsung setelah selesai di-instal
Filename: "{app}\Mulai_Aplikasi.bat"; Description: "Jalankan Aplikasi Sekarang"; Flags: nowait postinstall skipifsilent
