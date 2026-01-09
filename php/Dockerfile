FROM php:8.3-fpm

# 安裝 Node.js 和 npm
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# 系統套件
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    git \
    libcurl4-openssl-dev \
    pkg-config \
    libssl-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libwebp-dev \
    libxpm-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    libxslt-dev \
    libpq-dev \
    libsqlite3-dev \
    libmagickwand-dev \
    && rm -rf /var/lib/apt/lists/*


RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp

# 安装常用 PHP 
RUN docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_mysql \
    pdo_pgsql \
    mysqli \
    zip \
    gd \
    intl \
    bcmath \
    opcache \
    exif \
    mbstring \
    xml \
    soap \
    xsl \
    pcntl \
    sockets \
    calendar

# 安装 PECL 
RUN pecl install redis imagick \
    && docker-php-ext-enable redis imagick

# MongoDB extension
RUN pecl install mongodb \
    && docker-php-ext-enable mongodb

# 安裝 Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 設定 Composer 環境變數
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_HOME=/tmp

# 安裝 Laravel Installer
RUN composer global require laravel/installer
ENV PATH="${PATH}:/tmp/vendor/bin"

WORKDIR /var/www/html

EXPOSE 9000