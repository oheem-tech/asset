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
; HANYA membungkus mesin PHP, skrip downloader, dan icon aplikasi
Source: "php\*"; DestDir: "{app}\php"; Flags: ignoreversion recursesubdirs createallsubdirs
Source: "install_otomatis.php"; DestDir: "{app}"; Flags: ignoreversion
Source: "img\app_icon.ico"; DestDir: "{app}\img"; Flags: ignoreversion

[Icons]
; Membuat Shortcut di Desktop
Name: "{userdesktop}\Inventaris Aset"; Filename: "{app}\Buka_Aplikasi.bat"; IconFilename: "{app}\img\app_icon.ico"

; Membuat Shortcut Autorun/Startup agar server jalan otomatis di latar belakang saat Windows menyala
Name: "{userstartup}\Inventaris Aset Server"; Filename: "{app}\Start_Hidden.vbs"; WorkingDir: "{app}"

[Run]
; 1. Menjalankan skrip downloader secara senyap saat instalasi (mengambil source code dari GitHub)
Filename: "{app}\php\php.exe"; Parameters: """{app}\install_otomatis.php"""; WorkingDir: "{app}"; Flags: runhidden waituntilterminated; StatusMsg: "Mengunduh file sistem terbaru dari server..."

; 2. Langsung menyalakan server di latar belakang setelah instalasi selesai
Filename: "{app}\Start_Hidden.vbs"; Description: "Nyalakan Server Aset"; Flags: shellexec nowait postinstall skipifsilent

; 3. Buka aplikasi (browser)
Filename: "{app}\Buka_Aplikasi.bat"; Description: "Buka Aplikasi Sekarang"; Flags: shellexec nowait postinstall skipifsilent

[UninstallRun]
; Mematikan paksa mesin server di latar belakang sebelum folder instalasi dihapus
Filename: "taskkill"; Parameters: "/F /IM php.exe /T"; Flags: runhidden
