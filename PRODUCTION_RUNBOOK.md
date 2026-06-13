# Soul Whispers - Production Runbook

Quick reference for managing the production deployment on empire-command.

---

## Server Access

```bash
# SSH to server
ssh root@64.227.108.128

# App root
cd /var/www/html/soulwhispers
```

---

## Deployment Commands

### Initial Full Deploy (if needed again)
```bash
# From local machine
rsync -avz --exclude='config.php' --exclude='uploads/audio/*' --exclude='uploads/covers/*' --exclude='.git' /Users/chipmcallister/Projects/soulwhispers/ root@64.227.108.128:/var/www/html/soulwhispers/

# Then on server
ssh root@64.227.108.128 << 'EOF'
chmod 755 /var/www/html/soulwhispers/uploads
chmod 755 /var/www/html/soulwhispers/uploads/audio
chmod 755 /var/www/html/soulwhispers/uploads/covers
chown -R www-data:www-data /var/www/html/soulwhispers/uploads
EOF
```

### Update Code Only (no config, no uploads)
```bash
rsync -avz --exclude='config.php' --exclude='uploads/' --exclude='.git' /Users/chipmcallister/Projects/soulwhispers/ root@64.227.108.128:/var/www/html/soulwhispers/
```

### Backup Uploads
```bash
rsync -avz root@64.227.108.128:/var/www/html/soulwhispers/uploads/ /Users/chipmcallister/backups/soulwhispers-uploads/
```

---

## SSL/HTTPS Setup

### Get SSL Certificate from Let's Encrypt
```bash
ssh root@64.227.108.128 "certbot certonly --standalone -d soulwhispers.peoplestar.com -d www.soulwhispers.peoplestar.com"
```

### Enable SSL in Nginx Config
```bash
ssh root@64.227.108.128 << 'EOF'
# Uncomment SSL lines in production config
sed -i 's/# ssl_certificate/ssl_certificate/g' /etc/nginx/sites-available/soulwhispers-prod

# Enable the production config
ln -sf /etc/nginx/sites-available/soulwhispers-prod /etc/nginx/sites-enabled/default

# Test and reload
nginx -t && systemctl reload nginx

echo "✓ SSL enabled and Nginx reloaded"
EOF
```

### Verify HTTPS is Working
```bash
curl -I https://soulwhispers.peoplestar.com/
# Should see: HTTP/2 200
```

---

## Nginx Management

### Check Nginx Status
```bash
ssh root@64.227.108.128 "systemctl status nginx"
```

### Restart Nginx
```bash
ssh root@64.227.108.128 "systemctl restart nginx"
```

### Reload Nginx (no downtime)
```bash
ssh root@64.227.108.128 "nginx -t && systemctl reload nginx"
```

### View Access Logs
```bash
ssh root@64.227.108.128 "tail -f /var/log/nginx/soulwhispers-access.log"
```

### View Error Logs
```bash
ssh root@64.227.108.128 "tail -f /var/log/nginx/soulwhispers-error.log"
```

---

## PHP-FPM Management

### Check PHP-FPM Status
```bash
ssh root@64.227.108.128 "systemctl status php8.3-fpm"
```

### Restart PHP-FPM
```bash
ssh root@64.227.108.128 "systemctl restart php8.3-fpm"
```

### View PHP Logs
```bash
ssh root@64.227.108.128 "tail -f /var/log/php8.3-fpm.log"
```

---

## MySQL Management

### Check MySQL Status
```bash
ssh root@64.227.108.128 "systemctl status mysql"
```

### Connect to MySQL
```bash
ssh root@64.227.108.128 "mysql -u mcallpl -pamazing123 soulwhispers"
```

### Backup Database
```bash
ssh root@64.227.108.128 "mysqldump -u mcallpl -pamazing123 soulwhispers > /tmp/soulwhispers-backup-$(date +%Y%m%d-%H%M%S).sql"
```

### List All Poems
```bash
ssh root@64.227.108.128 << 'EOF'
mysql -u mcallpl -pamazing123 soulwhispers << 'SQL'
SELECT id, title, subtitle, audio_filename, created_at FROM poems ORDER BY sort_order, created_at DESC;
SQL
EOF
```

### Delete a Poem (by ID)
```bash
ssh root@64.227.108.128 << 'EOF'
# First get the poem details to know what files to delete
mysql -u mcallpl -pamazing123 soulwhispers << 'SQL'
SELECT id, title, audio_filename, cover_image FROM poems WHERE id = 1;
SQL

# Delete from DB
mysql -u mcallpl -pamazing123 soulwhispers << 'SQL'
DELETE FROM poems WHERE id = 1;
SQL

# Delete files manually
rm /var/www/html/soulwhispers/uploads/audio/FILENAME.mp3
rm /var/www/html/soulwhispers/uploads/covers/FILENAME.jpg
EOF
```

---

## Common Troubleshooting

### 502 Bad Gateway
```bash
# Check PHP-FPM socket
ssh root@64.227.108.128 "ls -l /run/php/php-fpm.sock"

# Restart PHP-FPM
ssh root@64.227.108.128 "systemctl restart php8.3-fpm"

# Check logs
ssh root@64.227.108.128 "tail -20 /var/log/php8.3-fpm.log"
```

### Permission Denied on Uploads
```bash
ssh root@64.227.108.128 << 'EOF'
# Fix ownership
chown -R www-data:www-data /var/www/html/soulwhispers/uploads

# Fix permissions
chmod 755 /var/www/html/soulwhispers/uploads
chmod 755 /var/www/html/soulwhispers/uploads/audio
chmod 755 /var/www/html/soulwhispers/uploads/covers

echo "✓ Upload permissions fixed"
EOF
```

### Database Connection Error
```bash
# Test connection
ssh root@64.227.108.128 "mysql -u mcallpl -pamazing123 -e 'SELECT VERSION();'"

# Check MySQL is running
ssh root@64.227.108.128 "systemctl status mysql"

# Verify config.php has correct credentials
ssh root@64.227.108.128 "grep -A 3 'db_' /var/www/html/soulwhispers/config.php"
```

### Nginx Not Reloading
```bash
# Test configuration
ssh root@64.227.108.128 "nginx -t"

# If syntax error, check the config
ssh root@64.227.108.128 "cat /etc/nginx/sites-enabled/soulwhispers | tail -50"

# Check if site is enabled
ssh root@64.227.108.128 "ls -l /etc/nginx/sites-enabled/soulwhispers*"
```

---

## Performance Monitoring

### Check Server Load
```bash
ssh root@64.227.108.128 "uptime"
```

### Check Disk Space
```bash
ssh root@64.227.108.128 "df -h"
```

### Check Memory Usage
```bash
ssh root@64.227.108.128 "free -h"
```

### Count Database Records
```bash
ssh root@64.227.108.128 "mysql -u mcallpl -pamazing123 soulwhispers -e 'SELECT COUNT(*) as poem_count FROM poems;'"
```

### List Upload Directory Size
```bash
ssh root@64.227.108.128 "du -sh /var/www/html/soulwhispers/uploads/*"
```

---

## Admin Operations

### Add a New Poem (via web)
1. Visit http://soulwhispers.peoplestar.com/admin
2. Click "Upload New Poem"
3. Fill in the form
4. Click "Upload Poem"

### Edit a Poem (via web)
1. Visit http://soulwhispers.peoplestar.com/admin
2. Find poem in table
3. Click "Edit"
4. Update fields
5. Click "Update Poem"

### Delete a Poem (via web)
1. Visit http://soulwhispers.peoplestar.com/admin
2. Find poem in table
3. Click "Delete"
4. Confirm deletion

### Add Poem via Database (if admin doesn't work)
```bash
ssh root@64.227.108.128 << 'EOF'
# Copy audio file
scp /path/to/audio.mp3 root@64.227.108.128:/var/www/html/soulwhispers/uploads/audio/

# Insert to database
mysql -u mcallpl -pamazing123 soulwhispers << 'SQL'
INSERT INTO poems (title, subtitle, lyrics, audio_filename, sort_order) VALUES (
  'Poem Title',
  'Subtitle',
  '[0:00] First line\n[0:05] Second line',
  'audio_filename.mp3',
  0
);
SQL
EOF
```

---

## SSL Certificate Renewal

Let's Encrypt certificates expire after 90 days. Certbot auto-renews by default.

### Check Certificate Expiry
```bash
ssh root@64.227.108.128 "certbot certificates"
```

### Manual Renewal (if needed)
```bash
ssh root@64.227.108.128 "certbot renew --force-renewal"
```

### View Renewal Logs
```bash
ssh root@64.227.108.128 "tail -50 /var/log/letsencrypt/letsencrypt.log"
```

---

## Emergency Procedures

### Take Site Down (maintenance)
```bash
ssh root@64.227.108.128 << 'EOF'
# Create maintenance page
cat > /var/www/html/soulwhispers/index.html << 'HTML'
<!DOCTYPE html>
<html>
<head><title>Maintenance</title></head>
<body style="text-align:center;padding:50px">
<h1>Soul Whispers is Under Maintenance</h1>
<p>We'll be back soon.</p>
</body>
</html>
HTML

# Reload Nginx
systemctl reload nginx
EOF
```

### Bring Site Back Online
```bash
ssh root@64.227.108.128 << 'EOF'
# Remove maintenance page
rm /var/www/html/soulwhispers/index.html

# Reload Nginx
systemctl reload nginx
EOF
```

### Restore from Backup
```bash
ssh root@64.227.108.128 << 'EOF'
# Stop services
systemctl stop nginx

# Restore uploads
rsync -avz --delete /path/to/backup/uploads/ /var/www/html/soulwhispers/uploads/

# Restore database (optional)
mysql -u mcallpl -pamazing123 soulwhispers < /tmp/soulwhispers-backup.sql

# Fix permissions
chown -R www-data:www-data /var/www/html/soulwhispers/uploads

# Start services
systemctl start nginx
EOF
```

---

## Useful Links

- **App Root**: /var/www/html/soulwhispers
- **Nginx Config**: /etc/nginx/sites-available/soulwhispers-prod
- **PHP-FPM Config**: /etc/php/8.3/fpm/php-fpm.conf
- **MySQL Data**: /var/lib/mysql/soulwhispers
- **Nginx Logs**: /var/log/nginx/
- **PHP Logs**: /var/log/php8.3-fpm.log

---

## Quick Test Commands

```bash
# Test public page
curl http://64.227.108.128/

# Test admin page
curl http://64.227.108.128/admin/

# Test API endpoint
curl http://64.227.108.128/api/poems.php | jq

# Test PHP processing
curl http://64.227.108.128/index.php | grep "Soul Whispers"

# Test database
mysql -u mcallpl -pamazing123 soulwhispers -e "SELECT COUNT(*) FROM poems;"
```

---

**Last Updated**: June 11, 2026  
**Server**: empire-command (64.227.108.128)  
**Domain**: soulwhispers.peoplestar.com
