# Imagen de desarrollo del backend PHP (Sistema de Archivo RRHH)

# 1. Imagen base: PHP 8.5 oficial, variante de línea de comandos.
FROM php:8.5-fpm AS base

# 2. Instalar la extensión de PHP para PostgreSQL.
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

# 3. Carpeta de trabajo dentro del contenedor.
WORKDIR /app

#==== Etapa desarrollo: servidor integrado, codigo montado ====
FROM base AS desarrollo

# 4. Puerto en el que escucha el servidor.
EXPOSE 8000

# 5. Comando que se ejecuta al arrancar el contenedor.
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]

#=== ETAPA DE DEPNDENCIAS: INSTALA VENDOR/ CON COMPOSER

FROM composer:2 AS dependencias

WORKDIR /app

#Solo los archivos de dependencias: asi se aprovecha la cache
#mientras  no cambien, aunque cambie el codigo PHP
COPY composer.json composer.lock ./

#Sin dependencias de desarrollo y con el autoload optimizado
RUN composer install --no-dev --no-interaction --no-progress --optimize-autoloader

#Etapa produccion PHP-FPM con el codigo dentro de la imagen
FROM base AS produccion

# Configuración de PHP para producción: la plantilla oficial + nuestros ajustes.
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php-produccion.ini "$PHP_INI_DIR/conf.d/zz-produccion.ini"

#las dependencias vienen de la etapa anterior, no de tu disco
COPY --from=dependencias /app/vendor ./vendor

#El codigo del backend . .dockerignore ecita que entren vendor/ y .env.
COPY . .

#Carpeta para informes y logs PHP-FPM trabaja con el usuario www-data,
#asi que edbe ser su dueño para poder escribir ahi

RUN mkdir -p storage/informes storage/logs \
    && chown -R www-data:www-data storage

EXPOSE 9000

#Sin CMD: se hereda "php-fpm" de la imagen oficial