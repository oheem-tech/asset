Add-Type -AssemblyName System.Drawing
$img = [System.Drawing.Image]::FromFile("C:\Users\custc\.gemini\antigravity\brain\82e70417-93e2-46f5-becd-e9045499ace8\.user_uploaded\media_1790561050477.png")
$bmp = New-Object System.Drawing.Bitmap($img)
$iconStream = New-Object System.IO.FileStream("d:\xampp8\htdocs\asset\img\app_icon.ico", [System.IO.FileMode]::Create)
# Creating a simple icon file format manually
$bw = New-Object System.IO.BinaryWriter($iconStream)
$bw.Write([int16]0) # Reserved
$bw.Write([int16]1) # Type 1 = ICO
$bw.Write([int16]1) # Image count

$width = $bmp.Width
$height = $bmp.Height
if ($width -gt 255) { $width = 0 } # 0 means 256
if ($height -gt 255) { $height = 0 } # 0 means 256

$bw.Write([byte]$width)
$bw.Write([byte]$height)
$bw.Write([byte]0) # Color count
$bw.Write([byte]0) # Reserved
$bw.Write([int16]0) # Color planes
$bw.Write([int16]32) # Bits per pixel

$ms = New-Object System.IO.MemoryStream
$bmp.Save($ms, [System.Drawing.Imaging.ImageFormat]::Png)
$pngBytes = $ms.ToArray()
$bw.Write([int]$pngBytes.Length) # Image size
$bw.Write([int]22) # Offset of image data

$bw.Write($pngBytes)
$bw.Close()
$iconStream.Close()
$ms.Close()
$img.Dispose()
$bmp.Dispose()
