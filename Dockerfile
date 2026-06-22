FROM php:8.2-apache

# Install MS SQL dependencies and drivers cleanly
ENV ACCEPT_EULA=Y
RUN apt-get update && apt-get install -y gnupg2 unixodbc-dev curl \
    && mkdir -p /usr/share/keyrings \
    && curl -fsSL https://packages.microsoft.com/keys/microsoft.asc | gpg --dearmor -o /usr/share/keyrings/microsoft-prod.gpg \
    && curl -fsSL https://packages.microsoft.com/config/debian/12/prod.list > /etc/apt/sources.list.d/mssql-release.list \
    && sed -i 's|arch=amd64|arch=amd64 signed-by=/usr/share/keyrings/microsoft-prod.gpg|g' /etc/apt/sources.list.d/mssql-release.list \
    && apt-get update && apt-get install -y msodbcsql18 mssql-tools18 \
    && pecl install pdo_sqlsrv sqlsrv \
    && docker-php-ext-enable pdo_sqlsrv sqlsrv

COPY . /var/www/html/
