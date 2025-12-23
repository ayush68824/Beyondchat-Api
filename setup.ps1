# BeyondChat Articles - Setup Script for Windows PowerShell
# This script helps set up all components of the project

Write-Host "=== BeyondChat Articles Setup ===" -ForegroundColor Cyan
Write-Host ""

# Check Python
Write-Host "Checking Python..." -ForegroundColor Yellow
try {
    $pythonVersion = python --version 2>&1
    Write-Host "✓ Python found: $pythonVersion" -ForegroundColor Green
    $pythonInstalled = $true
} catch {
    Write-Host "✗ Python not found. Please install Python 3.8+" -ForegroundColor Red
    Write-Host "  Download from: https://www.python.org/downloads/" -ForegroundColor Yellow
    $pythonInstalled = $false
}

# Check Node.js
Write-Host "Checking Node.js..." -ForegroundColor Yellow
try {
    $nodeVersion = node --version
    Write-Host "✓ Node.js found: $nodeVersion" -ForegroundColor Green
    $nodeInstalled = $true
} catch {
    Write-Host "✗ Node.js not found. Please install Node.js 18+" -ForegroundColor Red
    Write-Host "  Download from: https://nodejs.org/" -ForegroundColor Yellow
    $nodeInstalled = $false
}

# Check PHP
Write-Host "Checking PHP..." -ForegroundColor Yellow
try {
    $phpVersion = php --version 2>&1 | Select-Object -First 1
    Write-Host "✓ PHP found: $phpVersion" -ForegroundColor Green
    $phpInstalled = $true
} catch {
    Write-Host "✗ PHP not found. Please install PHP 8.1+" -ForegroundColor Red
    Write-Host "  Download from: https://windows.php.net/download/" -ForegroundColor Yellow
    $phpInstalled = $false
}

# Check Composer
Write-Host "Checking Composer..." -ForegroundColor Yellow
try {
    $composerVersion = composer --version 2>&1 | Select-Object -First 1
    Write-Host "✓ Composer found: $composerVersion" -ForegroundColor Green
    $composerInstalled = $true
} catch {
    Write-Host "✗ Composer not found. Please install Composer" -ForegroundColor Red
    Write-Host "  Download from: https://getcomposer.org/download/" -ForegroundColor Yellow
    $composerInstalled = $false
}

Write-Host ""
Write-Host "=== Installation Status ===" -ForegroundColor Cyan

# Install Python dependencies
if ($pythonInstalled) {
    Write-Host "Installing Python dependencies..." -ForegroundColor Yellow
    Set-Location scraper
    python -m pip install -r requirements.txt
    Set-Location ..
    Write-Host "✓ Python dependencies installed" -ForegroundColor Green
} else {
    Write-Host "⚠ Skipping Python dependencies (Python not found)" -ForegroundColor Yellow
}

# Install Node.js dependencies (already done, but verify)
if ($nodeInstalled) {
    Write-Host "Node.js dependencies already installed" -ForegroundColor Green
} else {
    Write-Host "⚠ Skipping Node.js dependencies (Node.js not found)" -ForegroundColor Yellow
}

# Install Laravel dependencies
if ($phpInstalled -and $composerInstalled) {
    Write-Host "Installing Laravel dependencies..." -ForegroundColor Yellow
    Set-Location laravel-api
    composer install
    Set-Location ..
    Write-Host "✓ Laravel dependencies installed" -ForegroundColor Green
} else {
    Write-Host "⚠ Skipping Laravel dependencies (PHP/Composer not found)" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "=== Next Steps ===" -ForegroundColor Cyan
Write-Host "1. Configure Laravel .env file in laravel-api/" -ForegroundColor White
Write-Host "2. Run: php artisan migrate (in laravel-api/)" -ForegroundColor White
Write-Host "3. Configure nodejs-script/.env with your LLM API key" -ForegroundColor White
Write-Host "4. Run the scraper: python scraper/scrape_articles.py" -ForegroundColor White
Write-Host "5. Import articles: php laravel-api/import_articles.php" -ForegroundColor White
Write-Host ""
Write-Host "Setup complete! Check README.md for detailed instructions." -ForegroundColor Green

