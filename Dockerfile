# Base image with Apache + PHP
FROM php:8.2-apache

# Set working directory
WORKDIR /var/www/html

# Install system dependencies (poppler-utils for pdftotext, unzip, git)
RUN apt-get update && \
    apt-get install -y \
    poppler-utils \
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

# Copy application files
COPY . .

# Fix permissions
RUN chown -R www-data:www-data /var/www/html

# Expose port 80
EXPOSE 80

# Start Apache in foreground
CMD ["apache2-foreground"]
