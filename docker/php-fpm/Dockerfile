# Reprise de l'image PHP 7.4 FastCGI Process Manager
FROM php:8.0-fpm

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
RUN apt-get install -y unzip libzip-dev libicu-dev zlib1g-dev libpq-dev libxml2-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install pdo pdo_pgsql pgsql \
    && docker-php-ext-install zip xml \
    && docker-php-ext-install opcache

RUN pecl install apcu \
    && docker-php-ext-enable apcu

# Installation de Composer
# -s rend l'installation silencieuse
# -S fait apparaître les erreurs s'il y en a
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer

# Symfony tool
RUN wget https://get.symfony.com/cli/installer -O - | bash && \
  mv /root/.symfony/bin/symfony /usr/local/bin/symfony

RUN version=$(php -r "echo PHP_MAJOR_VERSION.PHP_MINOR_VERSION;") \
    && architecture=$(case $(uname -m) in i386 | i686 | x86) echo "i386" ;; x86_64 | amd64) echo "amd64" ;; aarch64 | arm64 | armv8) echo "arm64" ;; *) echo "amd64" ;; esac) \
    && curl -A "Docker" -o /tmp/blackfire-probe.tar.gz -D - -L -s https://blackfire.io/api/v1/releases/probe/php/linux/$architecture/$version \
    && mkdir -p /tmp/blackfire \
    && tar zxpf /tmp/blackfire-probe.tar.gz -C /tmp/blackfire \
    && mv /tmp/blackfire/blackfire-*.so $(php -r "echo ini_get ('extension_dir');")/blackfire.so \
    && printf "extension=blackfire.so\nblackfire.agent_socket=tcp://blackfire:8307\n" > $PHP_INI_DIR/conf.d/blackfire.ini \
    && rm -rf /tmp/blackfire /tmp/blackfire-probe.tar.gz

# Paramètrage du timezone (fuseau horaire)
RUN rm /etc/localtime
RUN ln -s /usr/share/zoneinfo/Europe/Zurich /etc/localtime
RUN "date"

# Copie du fichier php.overrides.ini au bon endroit.
COPY ./php.overrides.ini /usr/local/etc/php/php.ini

WORKDIR /var/www/smarterd

EXPOSE 9000
