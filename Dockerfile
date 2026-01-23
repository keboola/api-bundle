FROM php:8.3-cli

# Set PHP memory limit
RUN echo "memory_limit = 512M" > /usr/local/etc/php/conf.d/memory-limit.ini

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    && docker-php-ext-install zip \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy composer files
COPY composer.json /app/

# Install dependencies
RUN composer install --no-interaction --prefer-dist --no-scripts

# Copy the rest of the application
COPY . /app

# Run composer install again to trigger scripts if needed
RUN composer install --no-interaction --prefer-dist

CMD ["php", "-v"]
