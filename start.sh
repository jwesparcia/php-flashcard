#!/bin/bash
# Render provides $PORT at runtime.
echo "Listen ${PORT}" > /etc/apache2/ports.conf
echo "<VirtualHost *:${PORT}>
    DocumentRoot /var/www/html
    <Directory /var/www/html>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>" > /etc/apache2/sites-enabled/000-default.conf
apache2ctl -D FOREGROUND
