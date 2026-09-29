FROM php:8.2-apache

# Install system dependencies and PHP extensions (PDO + MySQLi)
RUN apt-get update && apt-get install -y --no-install-recommends \
        libzip-dev \
        unzip \
        git \
    && docker-php-ext-install pdo pdo_mysql mysqli \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite for routing
RUN a2enmod rewrite

# Configure Apache to serve the application from the repository root
ENV APACHE_DOCUMENT_ROOT=/var/www/html
RUN sed -ri -e "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/sites-available/*.conf \
    && sed -ri -e "s!/var/www/!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Allow .htaccess overrides (needed for mod_rewrite based routing)
RUN sed -ri -e 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

WORKDIR /var/www/html

# Copy application source
COPY . /var/www/html

# Ensure Apache owns the application files
RUN chown -R www-data:www-data /var/www/html

# Railway (and similar platforms) provide the port to listen on via $PORT.
# Default Apache to port 80 and rewrite the listen/vhost config at container
# start time so the container also works when $PORT is injected at runtime.
ENV PORT=80
RUN sed -ri -e 's/80/${PORT}/g' /etc/apache2/ports.conf \
    && sed -ri -e 's/80/${PORT}/g' /etc/apache2/sites-available/000-default.conf

EXPOSE 80

CMD ["apache2-foreground"]
