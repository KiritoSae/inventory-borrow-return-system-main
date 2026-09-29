FROM php:8.2-fpm

# Install MySQL PHP extensions (PDO + MySQLi)
RUN docker-php-ext-install pdo_mysql mysqli

# Install nginx as the web server. This avoids the Apache
# "More than one MPM loaded" crash entirely by not using Apache at all.
RUN apt-get update \
    && apt-get install -y --no-install-recommends nginx \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

# Copy application source
COPY . /app

# Configure nginx to proxy PHP requests to php-fpm
COPY docker/nginx.conf /etc/nginx/sites-available/default

# Startup script that boots php-fpm and nginx together
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

# Ensure the app files are owned by the www-data user used by php-fpm/nginx
RUN chown -R www-data:www-data /app

EXPOSE 8080

CMD ["/start.sh"]
