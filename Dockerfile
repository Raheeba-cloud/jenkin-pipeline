# Base image: PHP with FPM support
FROM php:8.1-fpm-alpine

# Install nginx and bash (for debugging or multi-process startup)
RUN apk add --no-cache nginx bash curl

# Create required directories
RUN mkdir -p /var/www/html /run/nginx /var/log/nginx

# Copy application files
COPY app/ /var/www/html/

# Copy nginx configuration (replace default)
COPY app/nginx.conf /etc/nginx/nginx.conf

# Fix ownership and permissions
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

# Expose HTTP port
EXPOSE 80

# Health check (optional but good practice)
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
  CMD curl -f http://localhost/ || exit 1

# Start php-fpm (background) and nginx (foreground)
CMD ["sh", "-c", "php-fpm -D && nginx -g 'daemon off;'"]
