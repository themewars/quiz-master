#!/bin/bash

# QuizWhiz AI - Syntax Error Fix Script
# Run this on your server

echo "🔧 Fixing CreateQuizzes.php syntax error..."

# Navigate to project directory
cd /var/www/html/quizwhiz  # Change this to your actual project path

# Backup the file
cp app/Filament/User/Resources/QuizzesResource/Pages/CreateQuizzes.php app/Filament/User/Resources/QuizzesResource/Pages/CreateQuizzes.php.backup

# Fix the syntax error
sed -i "s/Don't check progress immediately/Don\\'t check progress immediately/g" app/Filament/User/Resources/QuizzesResource/Pages/CreateQuizzes.php

echo "✅ Syntax error fixed!"

# Clear caches
echo "🧹 Clearing caches..."
php artisan view:clear
php artisan config:clear
php artisan route:clear
php artisan cache:clear

echo "🎉 Fix completed! Try running php artisan view:clear again."
