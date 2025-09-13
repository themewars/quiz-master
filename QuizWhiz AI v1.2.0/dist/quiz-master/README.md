# 🎓 ExamGenerator AI v1.2.0

A powerful AI-powered exam and quiz generation platform built with Laravel and Filament.

## ✨ Features

- **AI-Powered Generation**: Create exams from text, PDF, images, and URLs using OpenAI
- **Multiple Export Formats**: PDF, Word, HTML exports
- **Professional Templates**: Standard, compact, and detailed exam templates
- **Answer Key Generation**: Automatic answer key creation
- **User Management**: Complete user registration and profile system
- **Admin Panel**: Filament-based admin interface
- **Exam Showcase**: Public exam gallery
- **Responsive Design**: Mobile-friendly interface
- **Multi-language Support**: Built-in language switching

## 🚀 Quick Start

### Prerequisites
- PHP 8.1 or higher
- Composer
- MySQL 5.7+ or MariaDB 10.3+
- Node.js & NPM (for frontend assets)

### Installation

1. **Clone/Download** the project files
2. **Install dependencies**:
   ```bash
   composer install --ignore-platform-reqs
   ```

3. **Environment setup**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Database setup**:
   ```bash
   # Create database
   mysql -u root -p
   CREATE DATABASE quizwhiz_ai;
   
   # Run migrations
   php artisan migrate
   
   # Create storage link
   php artisan storage:link
   ```

5. **Start development server**:
   ```bash
   php artisan serve
   ```

6. **Access the application**:
   - Main Site: http://localhost:8000
   - Admin Panel: http://localhost:8000/admin

## ⚙️ Configuration

### Environment Variables (.env)
```env
APP_NAME="ExamGenerator AI"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=quizwhiz_ai
DB_USERNAME=your_username
DB_PASSWORD=your_password

OPENAI_API_KEY=your_openai_api_key

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=tls
```

## 🎯 Usage

### Creating Exams
1. **Login** to your account
2. **Navigate** to "Create Exam"
3. **Choose input method**:
   - Text input
   - PDF upload
   - Image upload
   - URL extraction
4. **Configure settings**:
   - Number of questions
   - Difficulty level
   - Question types
5. **Generate** and review
6. **Export** in desired format

### Admin Panel
- **User Management**: Manage users and roles
- **Exam Management**: Review and moderate exams
- **System Settings**: Configure application settings
- **Analytics**: View usage statistics

## 🔧 Development

### Available Commands
```bash
# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Run migrations
php artisan migrate

# Install frontend dependencies
npm install
npm run dev

# Production build
npm run build
```

### Project Structure
```
app/
├── Filament/          # Admin panel resources
├── Http/             # Controllers and middleware
├── Models/           # Eloquent models
├── Services/         # Business logic
└── Utils/            # Utility classes

resources/
├── views/            # Blade templates
├── css/              # Stylesheets
└── js/               # JavaScript files

database/
├── migrations/       # Database migrations
└── seeders/          # Database seeders
```

## 🚀 Deployment

### Production Setup
1. **Upload files** to your server
2. **Set permissions**:
   ```bash
   chmod -R 755 /path/to/project
   chmod -R 775 storage bootstrap/cache
   chmod 600 .env
   ```

3. **Install production dependencies**:
   ```bash
   composer install --optimize-autoloader --no-dev
   ```

4. **Cache configurations**:
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

5. **Set up web server** (Apache/Nginx)
6. **Configure SSL certificate**

## 📚 Documentation

- **Setup Guide**: `SETUP_GUIDE.md`
- **Quick Start**: `QUICK_START.md`
- **Live Server Setup**: `LIVE_SERVER_SETUP.md`
- **Deployment Checklist**: `DEPLOYMENT_CHECKLIST.md`

## 🛠️ Troubleshooting

### Common Issues

1. **500 Server Error**:
   - Check file permissions
   - Verify .env configuration
   - Check web server error logs

2. **Database Connection Error**:
   - Verify database credentials in .env
   - Check database server status
   - Ensure database exists

3. **Permission Denied**:
   - Set proper ownership: `chown -R www-data:www-data /path/to/project`
   - Set proper permissions: `chmod -R 755 /path/to/project`

## 📞 Support

For support and questions:
- Check the documentation files
- Review Laravel documentation: https://laravel.com/docs
- Review Filament documentation: https://filamentphp.com/docs

## 📄 License

This project is licensed under the MIT License.

## 🎉 Success!

Your ExamGenerator AI is now ready to generate amazing exams and quizzes!

**Default Admin Credentials:**
- Email: admin@quizwhiz.ai
- Password: password (change immediately!)

---

**Happy Quiz Generating!** 🎓✨