FROM php:7.3-fpm

RUN DEBIAN_FRONTEND=noninteractive \
  apt-get update && \
  apt-get -y install \
    gettext \
    libssl-dev \
    unzip \
    wget \
    zip \
  && rm -rf /var/lib/apt/lists/*

# Xdebug
RUN pecl install xdebug \
  && docker-php-ext-enable xdebug

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

# Copy XDebug config file
COPY ./docker/php/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

# Copy entrypoint script
COPY docker/php/entrypoint /usr/local/bin

# Add symlinks for php-cs-fixer, phpunit, and psalm for easier running
RUN ln -s /usr/local/src/wellrested/vendor/bin/php-cs-fixer /usr/local/bin/php-cs-fixer
RUN ln -s /usr/local/src/wellrested/vendor/bin/phpunit /usr/local/bin/phpunit
RUN ln -s /usr/local/src/wellrested/vendor/bin/psalm /usr/local/bin/psalm

ENTRYPOINT ["entrypoint"]

CMD ["php-fpm"]

WORKDIR /usr/local/src/wellrested

USER www-data
