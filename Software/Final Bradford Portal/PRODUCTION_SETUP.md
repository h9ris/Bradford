# Production Environment Setup Guide

This guide covers deploying the Bradford Portal to a production environment.

## Prerequisites

- Linux server (Ubuntu 20.04+ recommended)
- Apache/Nginx web server
- PHP 7.4+ with required extensions
- MySQL 5.7+ or MariaDB 10.3+
- SSL certificate (Let's Encrypt recommended)
- Domain name

## 1. Server Setup

### Install Required Software

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install Apache, PHP, and MySQL
sudo apt install apache2 php7.4 php7.4-mysql php7.4-mbstring php7.4-xml php7.4-curl php7.4-zip php7.4-gd mysql-server -y

# Install PHP extensions for security
sudo apt install php7.4-bcmath php7.4-intl -y

# Secure MySQL installation
sudo mysql_secure_installation
```

### Configure PHP

Edit `/etc/php/7.4/apache2/php.ini`:

```ini
; Security settings
expose_php = Off
display_errors = Off
log_errors = On
error_log = /var/log/php_errors.log

; Session security
session.cookie_httponly = 1
session.cookie_secure = 1
session.cookie_samesite = "Strict"
session.gc_maxlifetime = 1440

; File upload limits
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 300

; Memory limit
memory_limit = 256M
```

### Configure Apache

Create virtual host `/etc/apache2/sites-available/bradford-portal.conf`:

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    ServerAlias www.your-domain.com
    DocumentRoot /var/www/bradford-portal
    
    <Directory /var/www/bradford-portal>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/bradford-portal_error.log
    CustomLog ${APACHE_LOG_DIR}/bradford-portal_access.log combined
    
    # Security headers
    <IfModule mod_headers.c>
        Header always set X-Frame-Options DENY
        Header always set X-Content-Type-Options nosniff
        Header always set Referrer-Policy strict-origin-when-cross-origin
        Header always set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' https://unpkg.com; style-src 'self' 'unsafe-inline' https://unpkg.com; img-src 'self' data: https:; font-src 'self'; connect-src 'self' https://*.tile.openstreetmap.org;"
    </IfModule>
</VirtualHost>
```

Enable the site:

```bash
sudo a2ensite bradford-portal
sudo a2enmod rewrite headers ssl
sudo systemctl restart apache2
```

## 2. SSL Certificate Setup

### Using Let's Encrypt (Free)

```bash
# Install Certbot
sudo apt install certbot python3-certbot-apache -y

# Get SSL certificate
sudo certbot --apache -d your-domain.com -d www.your-domain.com

# Set up auto-renewal
sudo crontab -e
# Add: 0 12 * * * /usr/bin/certbot renew --quiet
```

## 3. Database Setup

### Create Production Database

```bash
# Login to MySQL
sudo mysql -u root -p

# Create database and user
CREATE DATABASE bradford_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'portal_user'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT ALL PRIVILEGES ON bradford_portal.* TO 'portal_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Import Schema

```bash
# Import the schema
mysql -u portal_user -p bradford_portal < schema.sql
```

## 4. Application Deployment

### Upload Files

```bash
# Create web directory
sudo mkdir -p /var/www/bradford-portal
sudo chown -R www-data:www-data /var/www/bradford-portal

# Upload your application files to /var/www/bradford-portal
# Make sure to exclude development files like .git, tests, etc.
```

### Configure Application

1. Update `includes/db.php` with production database credentials
2. Update Google Maps API key in portal.php (use restricted key for production)
3. Update email settings in `includes/mailer.php` for production SMTP
4. Set proper file permissions:

```bash
sudo chown -R www-data:www-data /var/www/bradford-portal
sudo find /var/www/bradford-portal -type f -exec chmod 644 {} \;
sudo find /var/www/bradford-portal -type d -exec chmod 755 {} \;
```

## 5. Security Hardening

### Firewall Setup

```bash
# Install UFW
sudo apt install ufw -y
sudo ufw allow ssh
sudo ufw allow 'Apache Full'
sudo ufw --force enable
```

### PHP Security

Create `/var/www/bradford-portal/.htaccess`:

```apache
# Prevent access to sensitive files
<Files "config.php">
    Order Allow,Deny
    Deny from all
</Files>

<Files "*.sql">
    Order Allow,Deny
    Deny from all
</Files>

# Prevent PHP execution in uploads directory
<Directory "/var/www/bradford-portal/uploads">
    php_flag engine off
</Directory>
```

### Database Security

- Use strong passwords
- Restrict database user permissions
- Regular database backups:

```bash
# Create backup script
sudo nano /usr/local/bin/backup-portal.sh
```

```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u portal_user -p'your_password' bradford_portal > /var/backups/portal_$DATE.sql
find /var/backups -name "portal_*.sql" -mtime +7 -delete
```

```bash
sudo chmod +x /usr/local/bin/backup-portal.sh
sudo crontab -e
# Add: 0 2 * * * /usr/local/bin/backup-portal.sh
```

## 6. Monitoring & Maintenance

### Log Monitoring

```bash
# Monitor error logs
sudo tail -f /var/log/apache2/bradford-portal_error.log
sudo tail -f /var/log/php_errors.log
```

### Performance Monitoring

Install monitoring tools:

```bash
sudo apt install htop iotop -y
```

### Regular Updates

```bash
# Update system regularly
sudo apt update && sudo apt upgrade -y

# Update SSL certificates automatically (already configured)
```

## 7. Testing Production Deployment

### Pre-Launch Checklist

- [ ] Domain DNS configured
- [ ] SSL certificate installed
- [ ] Database connection working
- [ ] File permissions correct
- [ ] Google Maps API key configured
- [ ] Email functionality tested
- [ ] All pages load without errors
- [ ] Admin account created
- [ ] Backup system tested

### Post-Launch Monitoring

- Monitor error logs
- Check database performance
- Verify SSL certificate renewal
- Test all major functionality
- Monitor user feedback

## 8. Troubleshooting

### Common Issues

1. **500 Internal Server Error**
   - Check PHP error logs
   - Verify file permissions
   - Check database connection

2. **SSL Certificate Issues**
   - Verify domain DNS
   - Check Certbot logs
   - Ensure firewall allows HTTPS

3. **Database Connection Failed**
   - Verify credentials in db.php
   - Check MySQL service status
   - Confirm user permissions

4. **File Upload Issues**
   - Check upload directory permissions
   - Verify PHP upload settings
   - Check disk space

## 9. Backup & Recovery

### Automated Backups

- Database: Daily via cron
- Files: Weekly via rsync
- Offsite storage recommended

### Recovery Procedure

1. Restore database from backup
2. Restore files from backup
3. Update configuration if needed
4. Test application functionality

## 10. Scaling Considerations

For high-traffic deployments:

- Consider using a CDN for static assets
- Implement database connection pooling
- Use Redis for session storage
- Consider load balancing for multiple servers
- Implement rate limiting at server level (fail2ban)

---

**Note:** This is a basic production setup guide. For enterprise deployments, consider consulting with security professionals and using additional hardening measures.