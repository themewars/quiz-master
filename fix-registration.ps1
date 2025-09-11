# QuizWhiz AI - Registration Fix Script
# This script fixes common registration issues

Write-Host "🔧 QuizWhiz AI Registration Fix Script" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan

# Check if we're in the right directory
if (-not (Test-Path "artisan")) {
    Write-Host "❌ Error: Please run this script from the Laravel project root directory" -ForegroundColor Red
    exit 1
}

Write-Host "📋 Step 1: Running database migrations..." -ForegroundColor Yellow
try {
    php artisan migrate --force
    Write-Host "✅ Migrations completed successfully" -ForegroundColor Green
} catch {
    Write-Host "❌ Migration failed: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}

Write-Host "🌱 Step 2: Running database seeders..." -ForegroundColor Yellow
try {
    php artisan db:seed --class=RegistrationSetupSeeder
    Write-Host "✅ Registration setup seeder completed" -ForegroundColor Green
} catch {
    Write-Host "❌ Seeder failed: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}

Write-Host "🔍 Step 3: Running registration diagnostics..." -ForegroundColor Yellow
try {
    php artisan registration:fix
    Write-Host "✅ Diagnostics completed" -ForegroundColor Green
} catch {
    Write-Host "❌ Diagnostics failed: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host "🧪 Step 4: Testing registration flow..." -ForegroundColor Yellow
try {
    php artisan registration:test
    Write-Host "✅ Registration test completed" -ForegroundColor Green
} catch {
    Write-Host "❌ Registration test failed: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host "🧹 Step 5: Clearing caches..." -ForegroundColor Yellow
try {
    php artisan config:clear
    php artisan cache:clear
    php artisan route:clear
    php artisan view:clear
    Write-Host "✅ Caches cleared" -ForegroundColor Green
} catch {
    Write-Host "❌ Cache clearing failed: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""
Write-Host "🎉 Registration fix process completed!" -ForegroundColor Green
Write-Host ""
Write-Host "📝 Next steps:" -ForegroundColor Cyan
Write-Host "1. Test user registration through the web interface" -ForegroundColor White
Write-Host "2. Check admin panel for default plan configuration" -ForegroundColor White
Write-Host "3. Verify email verification is working" -ForegroundColor White
Write-Host ""
Write-Host "🔧 If you encounter issues, run: php artisan registration:fix" -ForegroundColor Yellow
