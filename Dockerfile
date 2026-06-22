FROM php:8.2-apache

# Install MS SQL dependencies and drivers
ENV ACCEPT_EULA=Y
RUN apt-get update && apt-get install -y gnupg2 unixodbc-dev curl \
    && curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add - \
    && curl https://packages.microsoft.com/config/debian/11/prod.list > /etc/apt/sources.list.d/mssql-release.list \
    && apt-get update && apt-get install -y msodbcsql18 mssql-tools18 \
    && pecl install pdo_sqlsrv sqlsrv \
    && docker-php-ext-enable pdo_sqlsrv sqlsrv

COPY . /var/www/html/
