FROM php:8.3-apache

RUN apt-get update && apt-get install -y --no-install-recommends git unzip libzip-dev libpng-dev libjpeg62-turbo-dev libwebp-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install pdo_mysql zip opcache gd \
    && a2enmod rewrite expires headers deflate \
    && rm -rf /var/lib/apt/lists/*

# Rendimiento: OPcache + caché de rutas de archivos (clave con volúmenes montados en Windows/macOS).
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-festival.ini
# Caché de navegador y compresión para CSS, JS e imágenes.
COPY docker/apache-cache.conf /etc/apache2/conf-enabled/zz-cache.conf

ENV OPCACHE_VALIDATE=1
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh && chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www/html
ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]
