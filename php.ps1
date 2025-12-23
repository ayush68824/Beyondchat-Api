# PHP wrapper script - Using XAMPP PHP
$phpPath = "C:\xampp\php\php.exe"
if (-not (Test-Path $phpPath)) {
    # Fallback to winget PHP
    $phpPath = "C:\Users\ayush\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe"
}
& $phpPath $args
