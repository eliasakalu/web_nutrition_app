FROM php:8.2-apache
COPY . /var/www/html/

# Install PDO MySQL driver
RUN docker-php-ext-install pdo pdo_mysql
