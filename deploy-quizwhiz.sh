#!/bin/bash

# QuizWhiz AI v1.2.0 - Automated Deployment Script
# Usage: bash deploy-quizwhiz.sh

echo "🚀 QuizWhiz AI v1.2.0 - Automated Deployment Script"
echo "=================================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    print_error "Please run as root (use sudo)"
    exit 1
fi

# Get domain name
read -p "Enter your domain name (e.g., quizwhiz.ai): " DOMAIN_NAME
if [ -z "$DOMAIN_NAME" ]; then
    print_error "Domain name is required!"
    exit 1
fi

# Get database credentials
read -p "Enter database name: " DB_NAME
read -p "Enter database username: " DB_USER
read -s -p "Enter database password: " DB_PASS
echo

if [ -z "$DB_NAME" ] || [ -z "$DB_USER" ] || [ -z "$DB_PASS" ]; then
    print_error "Database credentials are required!"
    exit 1
fi

# Get OpenAI API key
read -s -p "Enter OpenAI API key: " OPENAI_KEY
echo

if [ -z "$OPENAI_KEY" ]; then
    print_error "OpenAI API key is required!"
    exit 1
fi

print_status "Starting deployment for domain: $DOMAIN_NAME"

# Update system
print_status "Updating system packages..."
apt update && apt upgrade -y

# Install required packages
print_status "Installing required packages..."
apt install -y apache2 mysql-server php8.1 php8.1-cli php8.1-fpm php8.1-mysql php8.1-xml php8.1-gd php8.1-mbstring php8.1-curl php8.1-zip php8.1-intl php8.1-bcmath unzip git

# Install Composer
print_status "Installing Composer..."
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

# Enable Apache modules
print_status "Enabling Apache modules..."
a2enmod rewrite
a2enmod ssl
a2enmod headers

# Create project directory
PROJECT_DIR="/var/www/html/quizwhiz"
print_status "Creating project directory: $PROJECT_DIR"
mkdir -p $PROJECT_DIR
cd $PROJECT_DIR

# Clone repository
print_status "Cloning QuizWhiz AI repository..."
git clone https://github.com/themewars/quiz-master.git .

# Install dependencies
print_status "Installing Composer dependencies..."
composer install --optimize-autoloader --no-dev

# Set permissions
print_status "Setting file permissions..."
chown -R www-data:www-data $PROJECT_DIR
chmod -R 755 $PROJECT_DIR
chmod -R 775 $PROJECT_DIR/storage
chmod -R 775 $PROJECT_DIR/bootstrap/cache

# Create .env file
print_status "Creating .env file..."
cp .env.example .env

# Generate app key
print_status "Generating application key..."
php artisan key:generate

# Update .env with provided values
print_status "Configuring environment variables..."
sed -i "s/APP_NAME=Laravel/APP_NAME=\"QuizWhiz AI\"/" .env
sed -i "s/APP_ENV=local/APP_ENV=production/" .env
sed -i "s/APP_DEBUG=true/APP_DEBUG=false/" .env
sed -i "s|APP_URL=http://localhost|APP_URL=https://$DOMAIN_NAME|" .env
sed -i "s/DB_DATABASE=laravel/DB_DATABASE=$DB_NAME/" .env
sed -i "s/DB_USERNAME=root/DB_USERNAME=$DB_USER/" .env
sed -i "s/DB_PASSWORD=/DB_PASSWORD=$DB_PASS/" .env
sed -i "s/OPENAI_API_KEY=/OPENAI_API_KEY=$OPENAI_KEY/" .env

# Secure .env file
chmod 600 .env

# Create database
print_status "Creating database..."
mysql -u root -e "CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -e "CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';"
mysql -u root -e "GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';"
mysql -u root -e "FLUSH PRIVILEGES;"

# Run migrations
print_status "Running database migrations..."
php artisan migrate --force

# Create storage link
print_status "Creating storage symbolic link..."
php artisan storage:link

# Cache configurations
print_status "Caching configurations..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Create Apache virtual host
print_status "Creating Apache virtual host..."
cat > /etc/apache2/sites-available/quizwhiz.conf << EOF
<VirtualHost *:80>
    ServerName $DOMAIN_NAME
    ServerAlias www.$DOMAIN_NAME
    DocumentRoot $PROJECT_DIR/public
    
    <Directory $PROJECT_DIR/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog \${APACHE_LOG_DIR}/quizwhiz_error.log
    CustomLog \${APACHE_LOG_DIR}/quizwhiz_access.log combined
</VirtualHost>

<VirtualHost *:443>
    ServerName $DOMAIN_NAME
    ServerAlias www.$DOMAIN_NAME
    DocumentRoot $PROJECT_DIR/public
    
    <Directory $PROJECT_DIR/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/ssl-cert-snakeoil.pem
    SSLCertificateKeyFile /etc/ssl/private/ssl-cert-snakeoil.key
    
    ErrorLog \${APACHE_LOG_DIR}/quizwhiz_ssl_error.log
    CustomLog \${APACHE_LOG_DIR}/quizwhiz_ssl_access.log combined
</VirtualHost>
EOF

# Enable site
print_status "Enabling Apache site..."
a2ensite quizwhiz.conf
a2dissite 000-default.conf

# Restart Apache
print_status "Restarting Apache..."
systemctl restart apache2

# Setup SSL with Let's Encrypt (optional)
read -p "Do you want to setup SSL with Let's Encrypt? (y/n): " SETUP_SSL
if [ "$SETUP_SSL" = "y" ] || [ "$SETUP_SSL" = "Y" ]; then
    print_status "Installing Certbot..."
    apt install -y certbot python3-certbot-apache
    
    print_status "Obtaining SSL certificate..."
    certbot --apache -d $DOMAIN_NAME -d www.$DOMAIN_NAME --non-interactive --agree-tos --email admin@$DOMAIN_NAME
fi

# Setup cron job
print_status "Setting up cron job..."
(crontab -l 2>/dev/null; echo "* * * * * cd $PROJECT_DIR && php artisan schedule:run >> /dev/null 2>&1") | crontab -

# Setup firewall
print_status "Configuring firewall..."
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw --force enable

print_status "Deployment completed successfully!"
echo ""
echo "🎉 QuizWhiz AI v1.2.0 is now live!"
echo "=================================="
echo "🌐 Website: https://$DOMAIN_NAME"
echo "👨‍💼 Admin Panel: https://$DOMAIN_NAME/admin"
echo "📊 User Dashboard: https://$DOMAIN_NAME/user/dashboard"
echo ""
echo "📋 Default Admin Credentials:"
echo "   Email: admin@quizwhiz.ai"
echo "   Password: password"
echo ""
print_warning "Please change the default admin password immediately!"
echo ""
echo "📁 Project Directory: $PROJECT_DIR"
echo "📝 Logs: /var/log/apache2/quizwhiz_*.log"
echo "🗄️ Database: $DB_NAME"
echo ""
print_status "Setup completed! Your QuizWhiz AI is ready to use."
