#!/bin/bash

# QuizWhiz AI - Server Syntax Error Fix Script
# Run this on your server

echo "🔧 Fixing CreateQuizzes.php syntax error on server..."

# Navigate to project directory (change this to your actual path)
cd /var/www/html/public_html  # Change this to your actual project path

# Backup the file
echo "📁 Creating backup..."
cp "QuizWhiz AI v1.2.0/dist/quiz-master/app/Filament/User/Resources/QuizzesResource/Pages/CreateQuizzes.php" "QuizWhiz AI v1.2.0/dist/quiz-master/app/Filament/User/Resources/QuizzesResource/Pages/CreateQuizzes.php.backup"

# Fix the syntax error
echo "🔧 Fixing syntax error..."
sed -i "s/Don't check progress immediately/Do not check progress immediately/g" "QuizWhiz AI v1.2.0/dist/quiz-master/app/Filament/User/Resources/QuizzesResource/Pages/CreateQuizzes.php"

# Verify the fix
echo "✅ Verifying fix..."
if grep -q "Do not check progress immediately" "QuizWhiz AI v1.2.0/dist/quiz-master/app/Filament/User/Resources/QuizzesResource/Pages/CreateQuizzes.php"; then
    echo "✅ Fix applied successfully!"
else
    echo "❌ Fix failed!"
    exit 1
fi

# Clear caches
echo "🧹 Clearing caches..."
php artisan view:clear
php artisan config:clear
php artisan route:clear
php artisan cache:clear

echo "🎉 Fix completed! Try running php artisan view:clear again."
