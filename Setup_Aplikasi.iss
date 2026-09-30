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
; Mesin PHP (wajib ada, dibawa langsung oleh installer)
Source: "php\*"; DestDir: "{app}\php"; Flags: ignoreversion recursesubdirs createallsubdirs
; Skrip downloader (dijalankan saat instalasi untuk mengambil file aplikasi dari GitHub)
Source: "install_otomatis.php"; DestDir: "{app}"; Flags: ignoreversion
; Icon aplikasi
Source: "img\app_icon.ico"; DestDir: "{app}\img"; Flags: ignoreversion
; File-file startup WAJIB dibundel agar shortcut Desktop tidak pernah patah,
; bahkan jika download GitHub gagal sekalipun
Source: "Buka_Aplikasi.bat"; DestDir: "{app}"; Flags: ignoreversion
Source: "Jalankan_Server.bat"; DestDir: "{app}"; Flags: ignoreversion
Source: "Start_Hidden.vbs"; DestDir: "{app}"; Flags: ignoreversion

Source: "download_app.ps1"; DestDir: "{app}"; Flags: ignoreversion

[Icons]
; Membuat Shortcut di Desktop
Name: "{userdesktop}\Inventaris Aset"; Filename: "{app}\Buka_Aplikasi.bat"; IconFilename: "{app}\img\app_icon.ico"

[Registry]
; Mendaftarkan server ke Registry Windows agar otomatis menyala saat login (lebih andal dari Startup folder)
Root: HKCU; Subkey: "Software\Microsoft\Windows\CurrentVersion\Run"; ValueType: string; ValueName: "InventarisAsetServer"; ValueData: """{sys}\wscript.exe"" //B ""{app}\Start_Hidden.vbs"""; Flags: uninsdeletevalue

[Run]
; 1. Download file aplikasi dari GitHub menggunakan PowerShell (built-in, tidak diblokir antivirus)
Filename: "{sys}\WindowsPowerShell\v1.0\powershell.exe"; Parameters: "-ExecutionPolicy Bypass -WindowStyle Hidden -File ""{app}\download_app.ps1"" -AppDir ""{app}"""; WorkingDir: "{app}"; Flags: runhidden waituntilterminated; StatusMsg: "Mengunduh file sistem terbaru dari server..."

; 2. Langsung menyalakan server di latar belakang setelah instalasi selesai
Filename: "{sys}\wscript.exe"; Parameters: "//B ""{app}\Start_Hidden.vbs"""; WorkingDir: "{app}"; Description: "Nyalakan Server Aset"; Flags: nowait postinstall skipifsilent

; 3. Buka aplikasi (browser)
Filename: "{app}\Buka_Aplikasi.bat"; Description: "Buka Aplikasi Sekarang"; Flags: shellexec nowait postinstall skipifsilent

[UninstallRun]
; Mematikan paksa mesin server di latar belakang sebelum folder instalasi dihapus
Filename: "taskkill"; Parameters: "/F /IM php.exe /T"; Flags: runhidden; RunOnceId: "MatikanServer"

[UninstallDelete]
; Menghapus paksa seluruh file hasil download dari GitHub dan database agar bersih total
Type: filesandordirs; Name: "{app}"
