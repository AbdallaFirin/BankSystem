FROM php:8.2-fpm

# ── System dependencies ───────────────────────────────────────────────────────
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        xml \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# ── Node.js 20 LTS ───────────────────────────────────────────────────────────
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# ── Composer 2 ───────────────────────────────────────────────────────────────
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# ── Working directory ─────────────────────────────────────────────────────────
WORKDIR /var/www/html

# ── PHP configuration ─────────────────────────────────────────────────────────
RUN echo "upload_max_filesize = 20M"  >> /usr/local/etc/php/conf.d/app.ini \
 && echo "post_max_size = 20M"        >> /usr/local/etc/php/conf.d/app.ini \
 && echo "memory_limit = 256M"        >> /usr/local/etc/php/conf.d/app.ini \
 && echo "max_execution_time = 120"   >> /usr/local/etc/php/conf.d/app.ini

# ── Entrypoint ────────────────────────────────────────────────────────────────
COPY Docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
