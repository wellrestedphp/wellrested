FROM php:7.1-cli

RUN DEBIAN_FRONTEND=noninteractive \
  apt-get update && \
  apt-get -y install \
    gettext \
    libssl-dev \
    unzip \
    wget \
    zip \
  && rm -rf /var/lib/apt/lists/*

# Install Xdebug
RUN yes | pecl install xdebug \
  && echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/xdebug.ini

# Download and install Composer
COPY ./docker/php/install-composer.sh /tmp/install-composer.sh
RUN chmod +x /tmp/install-composer.sh; sync && \
  /tmp/install-composer.sh && \
  rm /tmp/install-composer.sh

# Create a directory for project sources and user's home directory
RUN mkdir /usr/local/src/wellrested && \
  chown -R www-data:www-data /usr/local/src/wellrested && \
  mkdir /var/www && \
  chown -R www-data:www-data /var/www

COPY ./src /usr/local/src/wellrested/src
COPY ./test /usr/local/src/wellrested/test
COPY ./composer.* /usr/local/src/wellrested/
COPY ./phpunit.xml.dist /usr/local/src/wellrested/

# Add symlink for phpunit for easier running
RUN ln -s /usr/local/src/wellrested/vendor/bin/phpunit /usr/local/bin/phpunit

WORKDIR /usr/local/src/wellrested

USER www-data

# Install Composer dependencies
RUN composer install
