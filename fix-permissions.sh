#!/bin/bash
# Fix Soul Whispers upload directory permissions
ssh root@64.227.108.128 << 'EOF'
chown -R www-data:www-data /var/www/html/soulwhispers/uploads
find /var/www/html/soulwhispers/uploads -type d -exec chmod 775 {} \;
find /var/www/html/soulwhispers/uploads -type f -exec chmod 644 {} \;
echo "Upload permissions fixed successfully"
EOF
