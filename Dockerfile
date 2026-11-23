FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    zip \
    unzip \
    default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions required: pdo_mysql, bcmath, intl, mbstring
RUN docker-php-ext-install pdo_mysql bcmath intl mbstring pcntl

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD ["php-fpm"]
