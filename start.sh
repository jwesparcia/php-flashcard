#!/bin/bash
# Set Apache to listen on Render's runtime PORT
echo "Listen ${PORT}" > /etc/apache2/ports.conf

# Configure default site to listen on $PORT
echo "<VirtualHost *:${PORT}>
    DocumentRoot /var/www/html
    <Directory /var/www/html>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>" > /etc/apache2/sites-enabled/000-default.conf

# Start Apache in the foreground
apache2ctl -D FOREGROUND
