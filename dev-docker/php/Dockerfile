FROM php:8.4-apache

# System packages needed to build the PHP extensions Contao requires
RUN apt-get update && apt-get install -y --no-install-recommends \
        libicu-dev \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libwebp-dev \
        libfreetype6-dev \
        libxml2-dev \
        libonig-dev \
        libsodium-dev \
        libgmp-dev \
        unzip \
        git \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
        intl \
        pdo_mysql \
        mysqli \
        gd \
        zip \
        exif \
        bcmath \
        gmp \
        sodium \
        opcache \
    && docker-php-ext-enable opcache

# mod_rewrite is required for the Symfony/Contao front controller
RUN a2enmod rewrite

COPY apache/vhost.conf /etc/apache2/sites-available/000-default.conf
COPY php/php.ini /usr/local/etc/php/conf.d/steelers.ini

# Composer, so `composer install` etc. can be run inside the container
# (see docker-compose.yml for why vendor/public/system/var are named
# volumes rather than part of the bind mount).
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Pre-create the named-volume mountpoints owned by www-data. Docker seeds a
# fresh named volume from the image at that path, ownership included, so the
# volumes come up writable by Apache's user and composer can run as www-data.
# Without this the volumes would be root-owned and Apache could not write.
RUN mkdir -p vendor public system var assets \
    && chown -R www-data:www-data /var/www/html

# Composer needs a writable home for its cache when running as www-data.
ENV COMPOSER_HOME=/tmp/composer
# The bind-mounted repo is owned by the host user, which git refuses to touch.
RUN git config --system --add safe.directory /var/www/html
