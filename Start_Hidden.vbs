Set WshShell = CreateObject("WScript.Shell")
' Menjalankan Mulai_Aplikasi.bat secara tersembunyi (0)
WshShell.Run chr(34) & "Mulai_Aplikasi.bat" & Chr(34), 0
Set WshShell = Nothing
