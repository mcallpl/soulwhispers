# Soul Whispers - Deployment Guide

## Local Development Setup

1. **Database Setup (already done)**:
   ```bash
   mysql -u mcallpl -pamazing123 < database.sql
   ```

2. **Run Local Server** (PHP 7.4+):
   ```bash
   cd /Users/chipmcallister/Projects/soulwhispers
   php -S localhost:8000
   ```

   Then visit:
   - Public: http://localhost:8000
   - Admin: http://localhost:8000/admin

3. **Test Upload**: Go to admin dashboard and upload a test poem with audio file.

---

## Production Deployment to empire-command

### 1. SSH into the server:
```bash
ssh root@64.227.108.128
```

### 2. Create production database:
```bash
mysql -u mcallpl -pamazing123 << 'EOF'
CREATE DATABASE IF NOT EXISTS soulwhispers CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE soulwhispers;

CREATE TABLE IF NOT EXISTS poems (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  subtitle VARCHAR(255) DEFAULT NULL,
  lyrics LONGTEXT DEFAULT NULL,
  audio_filename VARCHAR(255) NOT NULL,
  cover_image VARCHAR(255) DEFAULT NULL,
  sort_order INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_sort_order (sort_order),
  INDEX idx_created_at (created_at)
);
EOF
```

### 3. Deploy code from local to production:

**From your local machine**, run:
```bash
rsync -avz --exclude='config.php' --exclude='uploads/audio/*' --exclude='uploads/covers/*' --exclude='.git' --exclude='.gitignore' /Users/chipmcallister/Projects/soulwhispers/ root@64.227.108.128:/var/www/html/soulwhispers/
```

### 4. Create production config.php on server:
SSH into the server and create `/var/www/html/soulwhispers/config.php`:

```php
<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

$is_local = false;
$base_path = __DIR__;
$upload_dir = $base_path . '/uploads';

// Production: load from environment or use defaults
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('DB_USER') ?: 'mcallpl';
$db_pass = getenv('DB_PASS') ?: 'amazing123';
$db_name = 'soulwhispers';

try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
}

if (!is_dir($upload_dir . '/audio')) {
    mkdir($upload_dir . '/audio', 0755, true);
}
if (!is_dir($upload_dir . '/covers')) {
    mkdir($upload_dir . '/covers', 0755, true);
}
?>
```

### 5. Set permissions on server:
```bash
ssh root@64.227.108.128 << 'EOF'
cd /var/www/html/soulwhispers
chmod 755 uploads
chmod 755 uploads/audio
chmod 755 uploads/covers
chown -R www-data:www-data uploads
EOF
```

### 6. Nginx Configuration:

Add this server block to your nginx config (e.g., `/etc/nginx/sites-available/soulwhispers`):

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name soulwhispers.peoplestar.com www.soulwhispers.peoplestar.com;

    # Redirect to HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name soulwhispers.peoplestar.com www.soulwhispers.peoplestar.com;

    # SSL certificates (update with your actual paths)
    ssl_certificate /etc/letsencrypt/live/soulwhispers.peoplestar.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/soulwhispers.peoplestar.com/privkey.pem;

    root /var/www/html/soulwhispers;
    index index.php;

    # Gzip compression
    gzip on;
    gzip_types text/plain text/css text/javascript application/json;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # Pass PHP requests to FPM
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Deny direct access to sensitive files
    location ~ /\. {
        deny all;
    }

    location ~ ~$ {
        deny all;
    }

    # Allow uploads directory
    location /uploads {
        expires 7d;
        add_header Cache-Control "public";
    }

    # Logging
    access_log /var/log/nginx/soulwhispers-access.log;
    error_log /var/log/nginx/soulwhispers-error.log;
}
```

Enable the site:
```bash
ssh root@64.227.108.128 << 'EOF'
ln -s /etc/nginx/sites-available/soulwhispers /etc/nginx/sites-enabled/soulwhispers
nginx -t
systemctl reload nginx
EOF
```

### 7. SSL Certificate (if not already set up):

Using Let's Encrypt:
```bash
ssh root@64.227.108.128 "certbot certonly --standalone -d soulwhispers.peoplestar.com"
```

---

## Ongoing Maintenance

### Update code on production:
```bash
rsync -avz --exclude='config.php' --exclude='uploads/' --exclude='.git' /Users/chipmcallister/Projects/soulwhispers/ root@64.227.108.128:/var/www/html/soulwhispers/
```

### Backup uploads periodically:
```bash
rsync -avz root@64.227.108.128:/var/www/html/soulwhispers/uploads/ /Users/chipmcallister/backups/soulwhispers-uploads/
```

---

## Troubleshooting

**502 Bad Gateway**: Check PHP-FPM is running
```bash
sudo systemctl status php-fpm
```

**Permission denied on uploads**: Fix with:
```bash
sudo chown -R www-data:www-data /var/www/html/soulwhispers/uploads
sudo chmod 755 /var/www/html/soulwhispers/uploads
```

**Database connection error**: Verify credentials in config.php and MySQL is running
```bash
mysql -u mcallpl -pamazing123 -e "SELECT VERSION();"
```
