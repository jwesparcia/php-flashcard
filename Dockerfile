# Base image with Apache + PHP
FROM php:8.2-apache

# Set working directory
WORKDIR /var/www/html

# Install dependencies
RUN apt-get update && \
    apt-get install -y \
    poppler-utils \   # for pdftotext
    unzip \
    git && \
    rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy composer files first
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copy the rest of your app
COPY . .

# Fix permissions
RUN chown -R www-data:www-data /var/www/html

# Expose port
EXPOSE 80
# send PHP errors to stdout → Render “Logs” tab
RUN echo 'error_log = /dev/stderr' >> /usr/local/etc/php/conf.d/docker-php-log.ini

# 1. PHP errors → stdout (appears in Render Logs)
RUN echo 'error_log = /dev/stderr' >> /usr/local/etc/php/conf.d/docker-php-log.ini

# 2. Apache error log → stdout
RUN ln -sf /dev/stderr /var/log/apache2/error.log

# 3. Apache access log → stdout (optional, lets you see every hit)
RUN ln -sf /dev/stdout /var/log/apache2/access.log
# Start Apache
CMD ["apache2-foreground"]
