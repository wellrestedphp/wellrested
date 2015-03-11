#!/usr/bin/env bash

apt-get update
apt-get install -q -y git augeas-tools nginx php5 php5-fpm php5-cli php5-curl php5-xdebug python-pip

# Install or update composer.
if type composer &> /dev/null; then
  composer self-update
else
  curl -sS https://getcomposer.org/installer | php -- --filename=composer --install-dir=/usr/local/bin
fi

# Install Python dependencies
pip install sphinx sphinx_rtd_theme

# Install Composer dependencies
composer --working-dir=/vagrant install

# Run the unit tests.
cd /vagrant
vendor/bin/phpunit

# Build the documentation.
cd /vagrant/docs
make clean && make html

# Drop the user into the /vagrant directory on log in and dislay a message.
if ! grep /home/vagrant/.bashrc -e "cd /vagrant" &> /dev/null ; then
  echo "cd /vagrant" >> /home/vagrant/.bashrc
  echo "cat /vagrant/vagrant/log-in-message.txt" >> /home/vagrant/.bashrc
fi
