# PowerShell Script untuk Setup MySQL di Windows
# Run as Administrator

Write-Host "Setting up MySQL for Windows..." -ForegroundColor Green

# Check if MySQL is already installed
$mysqlService = Get-Service -Name "MySQL*" -ErrorAction SilentlyContinue
if ($mysqlService) {
            Write-Host "✓ MySQL service found: $($mysqlService.Name)" -ForegroundColor Green
    Write-Host "Service status: $($mysqlService.Status)" -ForegroundColor Cyan

    if ($mysqlService.Status -eq "Running") {
        Write-Host "MySQL is already running!" -ForegroundColor Green
        exit 0
    } else {
        Write-Host "Starting MySQL service..." -ForegroundColor Yellow
        Start-Service $mysqlService.Name
        Write-Host "✓ MySQL service started" -ForegroundColor Green
    }
} else {
            Write-Host "X MySQL service not found. Please install MySQL first." -ForegroundColor Red
    Write-Host "You can download MySQL from: https://dev.mysql.com/downloads/mysql/" -ForegroundColor Yellow
    Write-Host "Or use MySQL Installer: https://dev.mysql.com/downloads/installer/" -ForegroundColor Yellow
    exit 1
}

# Check if MySQL client is available
$mysqlPath = Get-Command mysql -ErrorAction SilentlyContinue
if (!$mysqlPath) {
            Write-Host "! MySQL client not found in PATH. You may need to add MySQL bin directory to PATH." -ForegroundColor Yellow
    Write-Host "Typical MySQL bin path: C:\Program Files\MySQL\MySQL Server 8.0\bin" -ForegroundColor Cyan
} else {
            Write-Host "✓ MySQL client found at: $($mysqlPath.Source)" -ForegroundColor Green
}

# Test MySQL connection
    Write-Host "Testing MySQL connection..." -ForegroundColor Yellow
try {
    # Try to connect with default credentials (adjust as needed)
    $mysqlTest = mysql -u root -p -e "SELECT VERSION();" 2>&1

    if ($LASTEXITCODE -eq 0) {
        Write-Host "✓ MySQL connection successful" -ForegroundColor Green
        Write-Host "MySQL version: $mysqlTest" -ForegroundColor Cyan
    } else {
        Write-Host "! MySQL connection failed. You may need to set root password." -ForegroundColor Yellow
        Write-Host "Run: mysql -u root -p" -ForegroundColor Cyan
    }
} catch {
            Write-Host "X Failed to test MySQL connection: $($_.Exception.Message)" -ForegroundColor Red
}

# Create database and user for SCADA dashboard
    Write-Host "Setting up database for SCADA dashboard..." -ForegroundColor Yellow

$dbName = "scada_dashboard"
$dbUser = "scada_user"
$dbPassword = "scada_password_2024"

$sqlCommands = @"
-- Create database
CREATE DATABASE IF NOT EXISTS \`$dbName\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user
CREATE USER IF NOT EXISTS '$dbUser'@'localhost' IDENTIFIED BY '$dbPassword';

-- Grant privileges
GRANT ALL PRIVILEGES ON \`$dbName\`.* TO '$dbUser'@'localhost';

-- Grant additional privileges for development
GRANT CREATE, DROP, ALTER, INDEX ON \`$dbName\`.* TO '$dbUser'@'localhost';

-- Flush privileges
FLUSH PRIVILEGES;

-- Show created database
SHOW DATABASES LIKE '$dbName';

-- Show user privileges
SHOW GRANTS FOR '$dbUser'@'localhost';
"@

$sqlFile = "$env:TEMP\setup_scada_db.sql"
try {
    Set-Content -Path $sqlFile -Value $sqlCommands -Encoding UTF8
            Write-Host "✓ SQL script created: $sqlFile" -ForegroundColor Green

            Write-Host "Executing SQL script..." -ForegroundColor Yellow
    Write-Host "You will be prompted for MySQL root password" -ForegroundColor Cyan

    # Execute SQL script
    mysql -u root -p < $sqlFile

    if ($LASTEXITCODE -eq 0) {
        Write-Host "✓ Database setup completed successfully!" -ForegroundColor Green
    } else {
        Write-Host "X Database setup failed. Please run manually:" -ForegroundColor Red
        Write-Host "   mysql -u root -p < $sqlFile" -ForegroundColor Cyan
    }

} catch {
            Write-Host "X Failed to create SQL script: $($_.Exception.Message)" -ForegroundColor Red
} finally {
    # Clean up
    if (Test-Path $sqlFile) {
        Remove-Item $sqlFile -Force
    }
}

# Create .env template for Laravel
$envTemplate = @"
# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=$dbName
DB_USERNAME=$dbUser
DB_PASSWORD=$dbPassword

# MySQL specific settings for SCADA data
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci

# Connection settings
DB_STRICT=false
DB_ENGINE=InnoDB

# Performance settings
DB_OPTIONS="--default-authentication-plugin=mysql_native_password"
"@

$envFile = "config\.env.mysql.template"
try {
    Set-Content -Path $envFile -Value $envTemplate -Encoding UTF8
            Write-Host "✓ Environment template created: $envFile" -ForegroundColor Green
    } catch {
        Write-Host "X Failed to create .env template: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host "MySQL setup completed!" -ForegroundColor Green
Write-Host "Database: $dbName" -ForegroundColor Cyan
Write-Host "User: $dbUser" -ForegroundColor Cyan
Write-Host "Password: $dbPassword" -ForegroundColor Cyan
Write-Host ".env template: $envFile" -ForegroundColor Cyan
Write-Host "Copy the template to your .env file and run: php artisan migrate" -ForegroundColor Yellow
