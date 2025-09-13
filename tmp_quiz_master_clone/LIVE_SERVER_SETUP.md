# 🚀 ExamGenerator.ai - Live Server Setup Guide

## 📋 **Prerequisites**

### **1. Server Requirements**
- **PHP**: 8.1 or higher ✅ (You have PHP 8.2.12)
- **Composer**: Latest version ✅ (Installed)
- **Database**: MySQL 5.7+ or MariaDB 10.3+
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **Extensions**: BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML, GD, Intl

### **2. Domain & Hosting**
- **Domain**: examgenerator.ai
- **Hosting**: Shared hosting, VPS, or Dedicated server
- **SSL Certificate**: Required for production

---

## 🔧 **Step 1: Server Preparation**

### **A. Install Required PHP Extensions**
```bash
# For Ubuntu/Debian
sudo apt update
sudo apt install php8.1-cli php8.1-fpm php8.1-mysql php8.1-xml php8.1-gd php8.1-mbstring php8.1-curl php8.1-zip php8.1-intl php8.1-bcmath

# For CentOS/RHEL
sudo yum install php-cli php-fpm php-mysql php-xml php-gd php-mbstring php-curl php-zip php-intl php-bcmath
```

### **B. Install Composer (if not installed)**
```bash
# Download and install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

---

## 📁 **Step 2: Upload Files to Server**

### **A. Upload Project Files**
```bash
# Method 1: Using FTP/SFTP
# Upload entire 'quiz-master' folder to your domain's public_html directory

# Method 2: Using Git (Recommended)
git clone https://github.com/your-repo/examgenerator-ai.git
cd examgenerator-ai
```

### **B. Set Proper Permissions**
```bash
# Set ownership
sudo chown -R www-data:www-data /var/www/html/examgenerator-ai

# Set permissions
sudo chmod -R 755 /var/www/html/examgenerator-ai
sudo chmod -R 775 /var/www/html/examgenerator-ai/storage
sudo chmod -R 775 /var/www/html/examgenerator-ai/bootstrap/cache
```

---

## ⚙️ **Step 3: Environment Configuration**

### **A. Create Production .env File**
```bash
# Copy example environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### **B. Configure .env for Production**
```env
APP_NAME="ExamGenerator AI"
APP_ENV=production
APP_KEY=base64:your-generated-key-here
APP_DEBUG=false
APP_URL=https://examgenerator.ai

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=examgenerator_ai
DB_USERNAME=your_db_username
DB_PASSWORD=your_db_password

# Mail Configuration (for notifications)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@examgenerator.ai
MAIL_FROM_NAME="ExamGenerator AI"

# OpenAI Configuration
OPENAI_API_KEY=your-openai-api-key

# Payment Gateways (if using)
RAZORPAY_KEY=your-razorpay-key
RAZORPAY_SECRET=your-razorpay-secret

STRIPE_KEY=your-stripe-key
STRIPE_SECRET=your-stripe-secret

PAYPAL_CLIENT_ID=your-paypal-client-id
PAYPAL_CLIENT_SECRET=your-paypal-secret
PAYPAL_MODE=sandbox

# File Storage
FILESYSTEM_DISK=public
```

---

## 🗄️ **Step 4: Database Setup**

### **A. Create Database**
```sql
-- Login to MySQL
mysql -u root -p

-- Create database
CREATE DATABASE examgenerator_ai CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user (optional)
CREATE USER 'examgenerator_user'@'localhost' IDENTIFIED BY 'strong_password';
GRANT ALL PRIVILEGES ON examgenerator_ai.* TO 'examgenerator_user'@'localhost';
FLUSH PRIVILEGES;
```

### **B. Run Migrations**
```bash
# Install dependencies
composer install --optimize-autoloader --no-dev

# Run migrations
php artisan migrate --force

# Seed database (optional)
php artisan db:seed --force
```

---

## 🌐 **Step 5: Web Server Configuration**

### **A. Apache Configuration (.htaccess)**
```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>

# Security Headers
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
</IfModule>

# Cache Control
<IfModule mod_expires.c>
    ExpiresActive on
    ExpiresByType text/css "access plus 1 year"
    ExpiresByType application/javascript "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
</IfModule>
```

### **B. Nginx Configuration**
```nginx
server {
    listen 80;
    listen 443 ssl http2;
    server_name examgenerator.ai www.examgenerator.ai;
    root /var/www/html/examgenerator-ai/public;

    # SSL Configuration
    ssl_certificate /path/to/ssl/certificate.crt;
    ssl_certificate_key /path/to/ssl/private.key;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

---

## 🔐 **Step 6: Security Configuration**

### **A. File Permissions**
```bash
# Secure sensitive files
chmod 600 .env
chmod 644 .htaccess

# Secure storage directories
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

### **B. Firewall Configuration**
```bash
# UFW (Ubuntu)
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable

# Firewalld (CentOS)
sudo firewall-cmd --permanent --add-service=ssh
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --reload
```

---

## 🚀 **Step 7: Final Setup Commands**

### **A. Install Dependencies**
```bash
# Install Composer dependencies
composer install --optimize-autoloader --no-dev

# Clear and cache configurations
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

### **B. Create Storage Link**
```bash
# Create symbolic link for storage
php artisan storage:link
```

### **C. Set Up Cron Jobs**
```bash
# Add to crontab
crontab -e

# Add this line for Laravel scheduler
* * * * * cd /var/www/html/examgenerator-ai && php artisan schedule:run >> /dev/null 2>&1
```

---

## ✅ **Step 8: Testing & Verification**

### **A. Test Application**
```bash
# Test database connection
php artisan migrate:status

# Test application
curl -I https://examgenerator.ai

# Check logs
tail -f storage/logs/laravel.log
```

### **B. Performance Optimization**
```bash
# Optimize Composer autoloader
composer dump-autoload --optimize

# Clear all caches
php artisan optimize:clear
php artisan optimize
```

---

## 🔧 **Step 9: Monitoring & Maintenance**

### **A. Log Monitoring**
```bash
# Monitor application logs
tail -f storage/logs/laravel.log

# Monitor web server logs
tail -f /var/log/apache2/error.log
# or
tail -f /var/log/nginx/error.log
```

### **B. Backup Strategy**
```bash
# Database backup script
#!/bin/bash
mysqldump -u username -p examgenerator_ai > backup_$(date +%Y%m%d_%H%M%S).sql

# Files backup
tar -czf files_backup_$(date +%Y%m%d_%H%M%S).tar.gz /var/www/html/examgenerator-ai
```

---

## 🆘 **Troubleshooting**

### **Common Issues:**

1. **500 Internal Server Error**
   - Check file permissions
   - Check .env configuration
   - Check web server error logs

2. **Database Connection Error**
   - Verify database credentials in .env
   - Check database server status
   - Verify database exists

3. **Permission Denied**
   - Set proper ownership: `chown -R www-data:www-data /path/to/project`
   - Set proper permissions: `chmod -R 755 /path/to/project`

4. **Composer Issues**
   - Update Composer: `composer self-update`
   - Clear Composer cache: `composer clear-cache`

---

## 📞 **Support**

For additional help:
- Check Laravel documentation: https://laravel.com/docs
- Check Filament documentation: https://filamentphp.com/docs
- Check server logs for specific errors

---

## 🎉 **Success!**

Your ExamGenerator.ai should now be live at:
**https://examgenerator.ai**

### **Available URLs:**
- **Main Site**: https://examgenerator.ai
- **Admin Panel**: https://examgenerator.ai/admin
- **Exam Showcase**: https://examgenerator.ai/exams
- **User Dashboard**: https://examgenerator.ai/user/dashboard
