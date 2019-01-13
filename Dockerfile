FROM php:7.2-apache
MAINTAINER Bruno Perel

RUN a2enmod rewrite

RUN apt-get update && apt-get install -y \
      git wget unzip mariadb-client \
      libpng-dev libfreetype6-dev libmcrypt-dev libjpeg-dev libpng-dev

RUN docker-php-ext-configure gd \
  --enable-gd-native-ttf \
  --with-freetype-dir=/usr/include/freetype2 \
  --with-png-dir=/usr/include \
  --with-jpeg-dir=/usr/include

RUN docker-php-ext-install opcache

RUN cd /usr/src && \
    wget http://xdebug.org/files/xdebug-2.6.1.tgz && \
    tar -xvzf xdebug-2.6.1.tgz && \
    cd xdebug-2.6.1 && \
    phpize && \
    ./configure && \
    make && \
    cp modules/xdebug.so /usr/local/lib/php/extensions/no-debug-non-zts-20170718

RUN mkdir -p /var/www/html/edges && \
    chown -R www-data:www-data /var/www/html/edges && \
    chmod a+w -R /var/www/html/edges