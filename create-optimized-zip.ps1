# ExamGenerator.ai - Optimized Zip Creator
# This script creates a production-ready zip file

Write-Host "🗜️ Creating Optimized Zip for Server Upload..." -ForegroundColor Green
Write-Host "===============================================" -ForegroundColor Green

# Create temporary directory for optimized files
$tempDir = "examgenerator-ai-optimized"
$zipName = "examgenerator-ai-production.zip"

# Remove existing temp directory
if (Test-Path $tempDir) {
    Remove-Item $tempDir -Recurse -Force
}

# Create temp directory
New-Item -ItemType Directory -Path $tempDir | Out-Null

Write-Host "📁 Copying essential files..." -ForegroundColor Cyan

# Copy essential directories
$essentialDirs = @("app", "bootstrap", "config", "database", "lang", "public", "resources", "routes", "storage")
foreach ($dir in $essentialDirs) {
    if (Test-Path $dir) {
        Copy-Item $dir -Destination $tempDir -Recurse -Force
        Write-Host "✅ Copied: $dir" -ForegroundColor Green
    }
}

# Copy essential files
$essentialFiles = @(".env.example", ".htaccess", "artisan", "composer.json", "composer.lock", "package.json", "README.md")
foreach ($file in $essentialFiles) {
    if (Test-Path $file) {
        Copy-Item $file -Destination $tempDir -Force
        Write-Host "✅ Copied: $file" -ForegroundColor Green
    }
}

# Copy our custom files
$customFiles = @("LIVE_SERVER_SETUP.md", "QUICK_START.md", "DEPLOYMENT_CHECKLIST.md", "env.production.example", "setup-windows.ps1")
foreach ($file in $customFiles) {
    if (Test-Path $file) {
        Copy-Item $file -Destination $tempDir -Force
        Write-Host "✅ Copied: $file" -ForegroundColor Green
    }
}

Write-Host "`n📦 Creating optimized vendor directory..." -ForegroundColor Cyan

# Create vendor directory
New-Item -ItemType Directory -Path "$tempDir/vendor" | Out-Null

# Copy only essential vendor packages (excluding heavy fonts)
$essentialVendorDirs = @(
    "barryvdh/laravel-dompdf",
    "filament/filament", 
    "filament/spatie-laravel-media-library-plugin",
    "laravel/framework",
    "laravel/sanctum",
    "laravel/socialite",
    "openai-php/client",
    "phpoffice/phpword",
    "smalot/pdfparser",
    "spatie/laravel-permission",
    "spatie/laravel-medialibrary"
)

foreach ($vendorDir in $essentialVendorDirs) {
    $sourcePath = "vendor/$vendorDir"
    $destPath = "$tempDir/vendor/$vendorDir"
    
    if (Test-Path $sourcePath) {
        # Create destination directory
        $destParent = Split-Path $destPath -Parent
        if (!(Test-Path $destParent)) {
            New-Item -ItemType Directory -Path $destParent -Force | Out-Null
        }
        
        Copy-Item $sourcePath -Destination $destPath -Recurse -Force
        Write-Host "✅ Copied vendor: $vendorDir" -ForegroundColor Green
    }
}

# Copy composer autoload files
if (Test-Path "vendor/autoload.php") {
    Copy-Item "vendor/autoload.php" -Destination "$tempDir/vendor/" -Force
    Write-Host "✅ Copied: autoload.php" -ForegroundColor Green
}

if (Test-Path "vendor/composer") {
    Copy-Item "vendor/composer" -Destination "$tempDir/vendor/" -Recurse -Force
    Write-Host "✅ Copied: composer autoload files" -ForegroundColor Green
}

Write-Host "`n🗑️ Removing heavy files..." -ForegroundColor Cyan

# Remove heavy font files from vendor
$fontPaths = @(
    "$tempDir/vendor/barryvdh/laravel-dompdf/src/dompdf/lib/fonts",
    "$tempDir/vendor/dompdf/dompdf/lib/fonts"
)

foreach ($fontPath in $fontPaths) {
    if (Test-Path $fontPath) {
        # Keep only essential fonts
        $essentialFonts = @("DejaVuSans.ttf", "DejaVuSans-Bold.ttf", "DejaVuSansMono.ttf")
        Get-ChildItem $fontPath -File | Where-Object { $_.Name -notin $essentialFonts } | Remove-Item -Force
        Write-Host "✅ Cleaned fonts in: $fontPath" -ForegroundColor Green
    }
}

# Remove test files
Get-ChildItem $tempDir -Recurse -File | Where-Object { $_.Name -like "*test*" -or $_.Name -like "*Test*" } | Remove-Item -Force
Write-Host "✅ Removed test files" -ForegroundColor Green

# Remove documentation files from vendor
Get-ChildItem $tempDir/vendor -Recurse -File | Where-Object { $_.Extension -eq ".md" -or $_.Extension -eq ".txt" -or $_.Extension -eq ".rst" } | Remove-Item -Force
Write-Host "✅ Removed vendor documentation" -ForegroundColor Green

Write-Host "`n📊 Calculating size..." -ForegroundColor Cyan

# Calculate size
$totalSize = (Get-ChildItem $tempDir -Recurse | Measure-Object -Property Length -Sum).Sum
$sizeMB = [math]::Round($totalSize / 1MB, 2)

Write-Host "📦 Optimized size: $sizeMB MB" -ForegroundColor Green

# Create zip file
Write-Host "`n🗜️ Creating zip file..." -ForegroundColor Cyan

if (Test-Path $zipName) {
    Remove-Item $zipName -Force
}

# Use .NET compression for better compression
Add-Type -AssemblyName System.IO.Compression.FileSystem
[System.IO.Compression.ZipFile]::CreateFromDirectory($tempDir, $zipName, [System.IO.Compression.CompressionLevel]::Optimal, $true)

# Get final zip size
$zipSize = (Get-Item $zipName).Length
$zipSizeMB = [math]::Round($zipSize / 1MB, 2)

Write-Host "✅ Zip created: $zipName" -ForegroundColor Green
Write-Host "📦 Final zip size: $zipSizeMB MB" -ForegroundColor Green

# Cleanup
Remove-Item $tempDir -Recurse -Force

Write-Host "`n🎉 Optimization Complete!" -ForegroundColor Green
Write-Host "========================" -ForegroundColor Green
Write-Host "Original size: ~241 MB" -ForegroundColor Yellow
Write-Host "Optimized size: $zipSizeMB MB" -ForegroundColor Green
Write-Host "Size reduction: $([math]::Round((241 - $zipSizeMB) / 241 * 100, 1))%" -ForegroundColor Green

Write-Host "`n📋 What's included:" -ForegroundColor Cyan
Write-Host "- All essential Laravel files" -ForegroundColor White
Write-Host "- Filament admin panel" -ForegroundColor White
Write-Host "- PDF/Word export functionality" -ForegroundColor White
Write-Host "- AI integration (OpenAI)" -ForegroundColor White
Write-Host "- Database migrations" -ForegroundColor White
Write-Host "- Setup documentation" -ForegroundColor White

Write-Host "`n🚀 Ready for server upload!" -ForegroundColor Green
