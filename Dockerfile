# Usa una imagen oficial de PHP con Apache
FROM php:8.1-apache

# Instala la extensión de PDO PostgreSQL
RUN apt-get update \
  && apt-get install -y libpq-dev \
  && docker-php-ext-install pdo pdo_pgsql

# Copia todo el código de tu app al directorio web
COPY php-server/app /var/www/html/

# Ajusta permisos
RUN chown -R www-data:www-data /var/www/html \
  && chmod -R 755 /var/www/html

# Expone el puerto 80
EXPOSE 80

# Instrucción por defecto (ya viene por Apache)
CMD ["apache2-foreground"]
