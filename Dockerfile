FROM php:8.2-fpm

# Instalar dependencias del sistema incluyendo libpq-dev para PostgreSQL
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    libpq-dev

# Instalar extensiones de PHP con soporte nativo de PostgreSQL (pdo_pgsql) y MySQL
RUN docker-php-ext-install pdo_pgsql pgsql pdo_mysql mbstring exif pcntl bcmath gd zip

# Obtener Composer oficial
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Directorio de trabajo
WORKDIR /var/www

EXPOSE 9000
CMD ["php-fpm"]
