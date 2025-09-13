# ExamGenerator.ai - Windows Setup Script
# Run this script in PowerShell as Administrator

Write-Host "🚀 ExamGenerator.ai Setup Script" -ForegroundColor Green
Write-Host "=================================" -ForegroundColor Green

# Check if running as Administrator
if (-NOT ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole] "Administrator")) {
    Write-Host "❌ This script requires Administrator privileges!" -ForegroundColor Red
    Write-Host "Please run PowerShell as Administrator and try again." -ForegroundColor Yellow
    exit 1
}

Write-Host "✅ Running as Administrator" -ForegroundColor Green

# Step 1: Check PHP Installation
Write-Host "`n📋 Step 1: Checking PHP Installation..." -ForegroundColor Cyan
try {
    $phpVersion = php --version
    Write-Host "✅ PHP is installed: $($phpVersion.Split("`n")[0])" -ForegroundColor Green
} catch {
    Write-Host "❌ PHP is not installed or not in PATH!" -ForegroundColor Red
    Write-Host "Please install PHP 8.1+ from https://windows.php.net/download/" -ForegroundColor Yellow
    exit 1
}

# Step 2: Check Composer Installation
Write-Host "`n📋 Step 2: Checking Composer Installation..." -ForegroundColor Cyan
try {
    $composerVersion = composer --version
    Write-Host "✅ Composer is installed: $($composerVersion.Split("`n")[0])" -ForegroundColor Green
} catch {
    Write-Host "❌ Composer is not installed globally!" -ForegroundColor Red
    Write-Host "Installing Composer locally..." -ForegroundColor Yellow
    
    # Download and install Composer
    Invoke-WebRequest -Uri "https://getcomposer.org/installer" -OutFile "composer-setup.php"
    php composer-setup.php
    Remove-Item composer-setup.php
    
    Write-Host "✅ Composer installed locally as composer.phar" -ForegroundColor Green
    Write-Host "Use: php composer.phar instead of composer" -ForegroundColor Yellow
}

# Step 3: Install Dependencies
Write-Host "`n📋 Step 3: Installing Dependencies..." -ForegroundColor Cyan
try {
    if (Test-Path "composer.phar") {
        php composer.phar install --ignore-platform-reqs
    } else {
        composer install --ignore-platform-reqs
    }
    Write-Host "✅ Dependencies installed successfully!" -ForegroundColor Green
} catch {
    Write-Host "❌ Failed to install dependencies!" -ForegroundColor Red
    Write-Host "Error: $($_.Exception.Message)" -ForegroundColor Yellow
    exit 1
}

# Step 4: Environment Setup
Write-Host "`n📋 Step 4: Setting up Environment..." -ForegroundColor Cyan
if (-not (Test-Path ".env")) {
    if (Test-Path ".env.example") {
        Copy-Item ".env.example" ".env"
        Write-Host "✅ Created .env file from .env.example" -ForegroundColor Green
    } else {
        Write-Host "❌ .env.example file not found!" -ForegroundColor Red
        exit 1
    }
} else {
    Write-Host "✅ .env file already exists" -ForegroundColor Green
}

# Step 5: Generate Application Key
Write-Host "`n📋 Step 5: Generating Application Key..." -ForegroundColor Cyan
try {
    if (Test-Path "composer.phar") {
        php composer.phar dump-autoload
        php artisan key:generate
    } else {
        composer dump-autoload
        php artisan key:generate
    }
    Write-Host "✅ Application key generated!" -ForegroundColor Green
} catch {
    Write-Host "❌ Failed to generate application key!" -ForegroundColor Red
    Write-Host "Error: $($_.Exception.Message)" -ForegroundColor Yellow
}

# Step 6: Create Storage Link
Write-Host "`n📋 Step 6: Creating Storage Link..." -ForegroundColor Cyan
try {
    php artisan storage:link
    Write-Host "✅ Storage link created!" -ForegroundColor Green
} catch {
    Write-Host "⚠️ Storage link creation failed (may already exist)" -ForegroundColor Yellow
}

# Step 7: Set Permissions (if possible)
Write-Host "`n📋 Step 7: Setting Permissions..." -ForegroundColor Cyan
try {
    # Set read permissions for all files
    Get-ChildItem -Path . -Recurse | ForEach-Object {
        $_.Attributes = $_.Attributes -bor [System.IO.FileAttributes]::ReadOnly
    }
    Write-Host "✅ Permissions set!" -ForegroundColor Green
} catch {
    Write-Host "⚠️ Permission setting failed (this is normal on Windows)" -ForegroundColor Yellow
}

# Step 8: Database Setup Instructions
Write-Host "`n📋 Step 8: Database Setup Instructions..." -ForegroundColor Cyan
Write-Host "`n🗄️ Database Setup Required:" -ForegroundColor Yellow
Write-Host "1. Install MySQL/MariaDB or use XAMPP/WAMP" -ForegroundColor White
Write-Host "2. Create a database named 'examgenerator_ai'" -ForegroundColor White
Write-Host "3. Update .env file with database credentials:" -ForegroundColor White
Write-Host "   DB_DATABASE=examgenerator_ai" -ForegroundColor Gray
Write-Host "   DB_USERNAME=your_username" -ForegroundColor Gray
Write-Host "   DB_PASSWORD=your_password" -ForegroundColor Gray
Write-Host "4. Run: php artisan migrate" -ForegroundColor White

# Step 9: Web Server Setup Instructions
Write-Host "`n📋 Step 9: Web Server Setup Instructions..." -ForegroundColor Cyan
Write-Host "`n🌐 Web Server Setup Required:" -ForegroundColor Yellow
Write-Host "1. Install Apache/Nginx or use XAMPP/WAMP" -ForegroundColor White
Write-Host "2. Point document root to: $PWD\public" -ForegroundColor White
Write-Host "3. Enable mod_rewrite for Apache" -ForegroundColor White
Write-Host "4. Configure virtual host for examgenerator.ai" -ForegroundColor White

# Step 10: Final Instructions
Write-Host "`n🎉 Setup Complete!" -ForegroundColor Green
Write-Host "=================" -ForegroundColor Green
Write-Host "`n📝 Next Steps:" -ForegroundColor Cyan
Write-Host "1. Configure your .env file with database credentials" -ForegroundColor White
Write-Host "2. Set up your web server (Apache/Nginx)" -ForegroundColor White
Write-Host "3. Run: php artisan migrate" -ForegroundColor White
Write-Host "4. Run: php artisan serve (for testing)" -ForegroundColor White
Write-Host "5. Visit: http://localhost:8000" -ForegroundColor White

Write-Host "`n📚 Documentation:" -ForegroundColor Cyan
Write-Host "- Setup Guide: LIVE_SERVER_SETUP.md" -ForegroundColor White
Write-Host "- Laravel Docs: https://laravel.com/docs" -ForegroundColor White
Write-Host "- Filament Docs: https://filamentphp.com/docs" -ForegroundColor White

Write-Host "`n🚀 Your ExamGenerator.ai is ready for deployment!" -ForegroundColor Green
