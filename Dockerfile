# Simple single-container image with nginx + php-fpm (alpine)
# Good for a simple demo/small form. For production consider multi-container (nginx + php-fpm) or proper process manager.
FROM php:8.1-fpm-alpine

# Install nginx and bash (for simple startup wrapper)
RUN apk add --no-cache nginx bash

# Create web root
RUN mkdir -p /var/www/html /run/nginx

# Copy nginx config
COPY app/nginx.conf /etc/nginx/conf.d/default.conf

# Copy app code
COPY app /var/www/html

# Ensure ownership
RUN chown -R www-data:www-data /var/www/html

# Expose port
EXPOSE 80

# Simple entrypoint: start php-fpm in foreground and run nginx (nginx foreground via daemon off)
# php-fpm -F (foreground), then run nginx in foreground
CMD ["sh", "-c", "php-fpm -F & nginx -g 'daemon off;'"]
