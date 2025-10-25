FROM php:8.1-fpm-alpine

# Install nginx and bash (for simple startup wrapper)
RUN apk add --no-cache nginx bash

# Create web root and nginx log/run dirs
RUN mkdir -p /var/www/html /run/nginx /var/log/nginx

# Copy proper nginx main config (we will copy app/nginx.conf into /etc/nginx/nginx.conf)
COPY app/nginx.conf /etc/nginx/nginx.conf

# Copy app code
COPY app /var/www/html

# Ensure ownership
RUN chown -R www-data:www-data /var/www/html

# Expose port
EXPOSE 80

# Start php-fpm and nginx (php-fpm foreground + nginx foreground)
CMD ["sh", "-c", "php-fpm -F & nginx -g 'daemon off;'"]
