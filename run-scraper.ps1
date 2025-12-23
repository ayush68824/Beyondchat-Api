# Run Python Scraper
Write-Host "=== Running Article Scraper ===" -ForegroundColor Cyan
Write-Host ""
cd backend\scraper
python scrape_articles.py
cd ..\..
