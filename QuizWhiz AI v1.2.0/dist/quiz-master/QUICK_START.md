# 🚀 ExamGenerator.ai - Quick Start Guide

## ⚡ **5-Minute Setup (Local Development)**

### **Step 1: Prerequisites**
```bash
# Check PHP version (8.1+ required)
php --version

# Check Composer (install if missing)
composer --version
```

### **Step 2: Install Dependencies**
```bash
# Install PHP packages
composer install --ignore-platform-reqs

# Generate application key
php artisan key:generate
```

### **Step 3: Environment Setup**
```bash
# Copy environment file
cp .env.example .env

# Edit .env file with your database credentials
# DB_DATABASE=examgenerator_ai
# DB_USERNAME=your_username
# DB_PASSWORD=your_password
```

### **Step 4: Database Setup**
```bash
# Create database (MySQL/MariaDB)
mysql -u root -p
CREATE DATABASE examgenerator_ai;

# Run migrations
php artisan migrate

# Create storage link
php artisan storage:link
```

### **Step 5: Start Development Server**
```bash
# Start Laravel development server
php artisan serve

# Visit: http://localhost:8000
```

---

## 🌐 **Production Deployment**

### **Option 1: Shared Hosting (cPanel)**
1. **Upload Files**: Upload all files to `public_html`
2. **Set Document Root**: Point to `public` folder
3. **Configure Database**: Create MySQL database
4. **Update .env**: Set production values
5. **Run Commands**: Via cPanel terminal or SSH

### **Option 2: VPS/Dedicated Server**
1. **Install LAMP/LEMP**: Apache/Nginx + MySQL + PHP
2. **Upload Files**: Via FTP/SFTP or Git
3. **Set Permissions**: `chmod -R 755` and `chown -R www-data:www-data`
4. **Configure Web Server**: Virtual host setup
5. **SSL Certificate**: Install Let's Encrypt

### **Option 3: Cloud Platforms**
- **DigitalOcean**: One-click Laravel droplet
- **AWS**: EC2 with RDS database
- **Google Cloud**: Compute Engine
- **Azure**: App Service

---

## 📋 **Essential Commands**

### **Development**
```bash
# Start development server
php artisan serve

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Run migrations
php artisan migrate

# Create storage link
php artisan storage:link
```

### **Production**
```bash
# Install production dependencies
composer install --optimize-autoloader --no-dev

# Cache configurations
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optimize application
php artisan optimize
```

---

## 🔧 **Configuration Files**

### **Environment (.env)**
```env
APP_NAME="ExamGenerator AI"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://examgenerator.ai

DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=examgenerator_ai
DB_USERNAME=your_username
DB_PASSWORD=your_password

OPENAI_API_KEY=your_openai_key
```

### **Web Server (.htaccess)**
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

---

## 🎯 **Key Features**

### **✅ What's Included:**
- **AI-Powered Exam Generation**: From text, PDF, images, URLs
- **Multiple Export Formats**: PDF, Word, HTML
- **Professional Exam Templates**: Standard, compact, detailed
- **Answer Key Generation**: Automatic answer key creation
- **User Management**: Registration, login, profiles
- **Admin Panel**: Filament-based admin interface
- **Exam Showcase**: Public exam gallery
- **Responsive Design**: Mobile-friendly interface

### **🔧 Customization:**
- **Branding**: Easy color and logo changes
- **Categories**: Custom exam categories
- **Templates**: Custom exam templates
- **Languages**: Multi-language support
- **Themes**: Custom CSS themes

---

## 📞 **Support & Resources**

### **Documentation**
- **Setup Guide**: `LIVE_SERVER_SETUP.md`
- **Deployment Checklist**: `DEPLOYMENT_CHECKLIST.md`
- **Environment Config**: `env.production.example`

### **Quick Links**
- **Laravel Docs**: https://laravel.com/docs
- **Filament Docs**: https://filamentphp.com/docs
- **OpenAI API**: https://platform.openai.com/docs

### **Common Issues**
1. **500 Error**: Check file permissions and .env configuration
2. **Database Error**: Verify database credentials
3. **Permission Denied**: Set proper ownership and permissions
4. **Composer Issues**: Update Composer and clear cache

---

## 🎉 **Success!**

Your ExamGenerator.ai is now ready! 

**Access Points:**
- **Main Site**: http://localhost:8000 (development)
- **Admin Panel**: http://localhost:8000/admin
- **Exam Showcase**: http://localhost:8000/exams
- **User Dashboard**: http://localhost:8000/user/dashboard

**Default Admin Credentials:**
- **Email**: admin@examgenerator.ai
- **Password**: password (change immediately!)

---

## 🚀 **Next Steps**

1. **Configure OpenAI API**: Add your API key in .env
2. **Customize Branding**: Update colors, logos, and text
3. **Add Content**: Create sample exams and categories
4. **Test Features**: Verify all functionality works
5. **Deploy to Production**: Follow deployment guide

**Happy Exam Generating!** 🎓
