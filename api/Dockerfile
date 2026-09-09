FROM php:8.4-fpm-alpine

# Dependências de sistema e extensões PHP
RUN apk update && apk add --no-cache \
    git \
    unzip \
    icu-dev \
    libzip-dev \
    linux-headers \
    && docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    intl \
    opcache \
    zip

# Instalação do Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copiar arquivos de dependências
COPY composer.json composer.lock symfony.lock ./

# Instalar dependências sem scripts para cache de build
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copiar código da aplicação
COPY . .

# Finalizar autoload e scripts do composer
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative \
    && composer run-script --no-dev post-install-cmd

# Diretório de persistência para uploads isolados por tenant
RUN mkdir -p var/storage var/cache var/log \
    && chown -R www-data:www-data var/

VOLUME ["/var/www/html/var/storage"]

EXPOSE 9000

CMD ["php-fpm"]
