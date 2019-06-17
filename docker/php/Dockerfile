FROM php:7.2-cli

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

 # Install Composer.
RUN curl -sS https://getcomposer.org/installer | php -- \
  --filename=composer --install-dir=/usr/local/bin

# Install dumb-init.
RUN wget -O /usr/local/bin/dumb-init https://github.com/Yelp/dumb-init/releases/download/v1.2.1/dumb-init_1.2.1_amd64
RUN chmod +x /usr/local/bin/dumb-init

# Create a directory for project sources and user's home directory
RUN mkdir /usr/local/src/wellrested && \
  chown -R www-data:www-data /usr/local/src/wellrested && \
  chown -R www-data:www-data /var/www

COPY ./src /usr/local/src/wellrested/src
COPY ./test /usr/local/src/wellrested/test
COPY ./composer.* /usr/local/src/wellrested/
COPY ./phpunit.xml.dist /usr/local/src/wellrested/

# Add symlink for phpunit for easier running
RUN ln -s /usr/local/src/wellrested/vendor/bin/phpunit /usr/local/bin/phpunit

WORKDIR /usr/local/src/wellrested

USER www-data

ENTRYPOINT ["dumb-init", "--"]

RUN composer install
