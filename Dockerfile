FROM php:8.2-apache

# Enable Apache mod_rewrite for SPA routing
RUN a2enmod rewrite

# Copy project files to the Apache web root
COPY . /var/www/html/

# Ensure proper permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
