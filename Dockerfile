FROM php:8.2-apache

# Install MySQL PHP extensions (PDO + MySQLi)
RUN docker-php-ext-install pdo_mysql mysqli

# Avoid the "More than one MPM loaded" error by disabling mpm_prefork
# (enabled by default in the base image) and enabling mpm_event, along
# with mod_rewrite for routing.
RUN a2dismod mpm_prefork \
    && a2enmod mpm_event rewrite

# Set the DocumentRoot to /var/www/html and allow .htaccess overrides
# (needed for mod_rewrite based routing).
ENV APACHE_DOCUMENT_ROOT=/var/www/html
RUN sed -ri -e 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf

WORKDIR /var/www/html

# Copy application source
COPY . /var/www/html

# Ensure Apache owns the application files
RUN chown -R www-data:www-data /var/www/html

# Apache listens on port 80 by default; Railway handles the port mapping.
EXPOSE 80

CMD ["apache2-foreground"]
