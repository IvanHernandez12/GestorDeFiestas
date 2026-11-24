FROM node:22-alpine AS frontend

WORKDIR /var/www/html

COPY package.json package-lock.json ./
RUN npm ci

COPY vite.config.js ./
COPY resources ./resources
COPY public ./public

ARG VITE_GOOGLE_MAPS_API_KEY=
ENV VITE_GOOGLE_MAPS_API_KEY=${VITE_GOOGLE_MAPS_API_KEY}

RUN npm run build

FROM php:8.4-fpm-alpine AS app

RUN apk add --no-cache freetype-dev icu-dev libjpeg-turbo-dev libpng-dev libzip-dev \
        oniguruma-dev sqlite-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" bcmath gd intl mbstring pdo_mysql pdo_sqlite zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader \
    && mkdir -p storage/app/sqlite storage/app/public storage/framework/cache/data \
        storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && touch storage/app/sqlite/database.sqlite \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

COPY --from=frontend /var/www/html/public/build ./public/build

EXPOSE 9000

CMD ["php-fpm"]

FROM nginx:alpine AS web

COPY --from=frontend /var/www/html/public /var/www/html/public
RUN ln -s ../storage/app/public /var/www/html/public/storage
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

EXPOSE 80
