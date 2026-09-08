FROM php:8.1-apache

# mysqli + rewrite for the PHP app (index.php + .htaccess routing)
RUN docker-php-ext-install mysqli && a2enmod rewrite

WORKDIR /var/www/html
COPY . .

# Remove Node artifacts and local-only junk — PHP app only.
RUN rm -rf node_modules graphify-out server.js start.js server.test.js package.json package-lock.json && \
    sed -i 's/DirectoryIndex/DirectoryIndex index.php/' /etc/apache2/mods-enabled/dir.conf && \
    sed -i 's/Listen 80/Listen 4005/' /etc/apache2/ports.conf && \
    sed -i 's|:80>|:4005>|g' /etc/apache2/sites-available/000-default.conf

EXPOSE 4005
