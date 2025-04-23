FROM php:8.0-apache

# Instalar extensiones PHP para MySQL
RUN docker-php-ext-install pdo pdo_mysql

# Habilitar mod_rewrite para URLs amigables
RUN a2enmod rewrite

# Copiar la aplicación al contenedor
COPY app/ /var/www/html/

# Configurar Apache para usar /var/www/html como raíz
ENV APACHE_DOCUMENT_ROOT /var/www/html
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf