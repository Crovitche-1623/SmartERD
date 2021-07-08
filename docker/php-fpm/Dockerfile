# Reprise de l'image PHP 7.4 FastCGI Process Manager
FROM php:7.4-fpm

# Mise à jour de la liste des paquets et de leur version. Cette commande
# n'installe rien du tout.
RUN apt-get update


RUN apt-get install -y \
    wget \
    git

# Les extensions sont repris de https://symfony.com/doc/current/setup.html.
# zlib1g-dev permet de dezipper des fichiers : https://packages.debian.org/fr/jessie/zlib1g-dev
# libpq-dev de communiquer avec postgresql : https://packages.debian.org/fr/sid/libpq-dev
# git : le nom parle de lui-même
# libicu-dev : https://packages.debian.org/fr/sid/libicu-dev
# libxml2-dev :
RUN apt-get install -y libzip-dev libicu-dev zlib1g-dev libpq-dev libxml2-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install pdo pdo_pgsql pgsql \
    && docker-php-ext-install zip xml \
    && docker-php-ext-install opcache

# Installation de Composer
# -s rend l'installation silencieuse
# -S fait apparaître les erreurs s'il y en a
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer

# Symfony tool
RUN wget https://get.symfony.com/cli/installer -O - | bash && \
  mv /root/.symfony/bin/symfony /usr/local/bin/symfony

# Paramètrage du timezone (fuseau horaire)
RUN rm /etc/localtime
RUN ln -s /usr/share/zoneinfo/Europe/Zurich /etc/localtime
RUN "date"

# Copie du fichier php.overrides.ini au bon endroit.
COPY ./php.overrides.ini /usr/local/etc/php/php.ini

WORKDIR /var/www/smarterd

EXPOSE 9000
