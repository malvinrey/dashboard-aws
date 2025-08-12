# PowerShell Script untuk Install Nginx di Windows
# Run as Administrator

Write-Host "Installing Nginx for Windows..." -ForegroundColor Green

# Buat direktori untuk Nginx
$nginxDir = "C:\nginx"
if (!(Test-Path $nginxDir)) {
    New-Item -ItemType Directory -Path $nginxDir -Force
            Write-Host "✓ Created Nginx directory: $nginxDir" -ForegroundColor Green
}

# Download Nginx stable version
$nginxUrl = "http://nginx.org/download/nginx-1.24.0.zip"
$nginxZip = "$env:TEMP\nginx.zip"

Write-Host "Downloading Nginx..." -ForegroundColor Yellow
try {
    Invoke-WebRequest -Uri $nginxUrl -OutFile $nginxZip
            Write-Host "✓ Nginx downloaded successfully" -ForegroundColor Green
    } catch {
        Write-Host "X Failed to download Nginx: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}

# Extract Nginx
    Write-Host "Extracting Nginx..." -ForegroundColor Yellow
    try {
        Expand-Archive -Path $nginxZip -DestinationPath $nginxDir -Force
        Write-Host "✓ Nginx extracted successfully" -ForegroundColor Green
    } catch {
        Write-Host "X Failed to extract Nginx: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}

# Rename extracted folder
$extractedFolder = Get-ChildItem -Path $nginxDir -Directory | Where-Object { $_.Name -like "nginx-*" } | Select-Object -First 1
if ($extractedFolder) {
    $nginxPath = Join-Path $nginxDir "nginx"
    if (Test-Path $nginxPath) {
        Remove-Item $nginxPath -Recurse -Force
    }
    Rename-Item $extractedFolder.FullName $nginxPath
    Write-Host "✓ Nginx folder renamed to: $nginxPath" -ForegroundColor Green
}

# Clean up
Remove-Item $nginxZip -Force

Write-Host "Nginx installation completed!" -ForegroundColor Green
Write-Host "Nginx location: $nginxPath" -ForegroundColor Cyan
Write-Host "To start Nginx, run: $nginxPath\nginx.exe" -ForegroundColor Cyan
Write-Host "To stop Nginx, run: $nginxPath\nginx.exe -s stop" -ForegroundColor Cyan
