# Use official PHP image with Apache
FROM php:8.2-apache

# Enable Apache rewrite module
RUN a2enmod rewrite

# Install essential system dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install zip

# Set working directory
WORKDIR /var/www/html

# Copy application files into container
COPY . /var/www/html/

# Create a writable cache directory for the Jikan proxy layer
RUN mkdir -p /var/www/html/cache && chmod -R 777 /var/www/html/cache

# Configure Apache to listen on Render's dynamic $PORT environment variable
RUN sed -i 's/80/${PORT}/g' /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf

# Expose port (Render handles mapping automatically)
EXPOSE ${PORT}
