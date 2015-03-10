#!/usr/bin/env bash

apt-get update
apt-get install -q -y git augeas-tools nginx php5 php5-fpm php5-cli php5-curl php5-xdebug python-pip

# Install or update composer.
if type composer &> /dev/null; then
  composer self-update
else
  curl -sS https://getcomposer.org/installer | php -- --filename=composer --install-dir=/usr/local/bin
fi

pip install sphinx sphinx_rtd_theme

composer --working-dir=/vagrant install

if ! grep /home/vagrant/.bashrc -e "cd /vagrant" &> /dev/null ; then
  echo "cd /vagrant" >> /home/vagrant/.bashrc
  echo "cat /vagrant/vagrant/log-in-message.txt" >> /home/vagrant/.bashrc
fi
