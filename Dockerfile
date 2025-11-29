# Use PHP CLI for built-in server
FROM php:8.2-cli

# Install system dependencies
RUN apt-get update && apt-get install -y git unzip && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy composer files first for caching
COPY composer.json composer.lock ./

# Install dependencies inside container
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copy all app files
COPY . .

# Create uploads folder with correct permissions
RUN mkdir -p /app/uploads && chmod 777 /app/uploads

# Ensure PHP can read files
RUN find /app -type d -exec chmod 755 {} \; \
    && find /app -type f -exec chmod 644 {} \;

# Expose Render port
EXPOSE $PORT

# Start PHP built-in server
CMD php -S 0.0.0.0:$PORT -t . router.php
