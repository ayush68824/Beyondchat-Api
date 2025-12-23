# Start Laravel API Server
Write-Host "=== Starting Laravel API ===" -ForegroundColor Cyan
Write-Host ""
cd backend\laravel-api
..\..\php.ps1 artisan serve
cd ..\..
