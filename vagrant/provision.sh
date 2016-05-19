#!/usr/bin/env bash

# PHP-5.6 repository
if ! apt-cache policy | grep ondrej/php5-5.6 ; then
  apt-add-repository -y ppa:ondrej/php5-5.6
fi

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

# Configure software
changes="
set /files/etc/php5/cli/php.ini/Date/date.timezone America/New_York
set /files/etc/php5/fpm/php.ini/Date/date.timezone America/New_York
set /files/etc/php5/fpm/php.ini/cgi/cgi.fix_pathinfo 0
set /files/etc/php5/fpm/php.ini/Session/session.save_path 127.0.0.1:11211
set /files/etc/php5/mods-available/xdebug.ini/.anon/zend_extension xdebug.so
set /files/etc/php5/mods-available/xdebug.ini/.anon/xdebug.remote_enable on
set /files/etc/php5/mods-available/xdebug.ini/.anon/xdebug.remote_connect_back on
set /files/etc/php5/fpm/pool.d/www.conf/www/listen /var/run/php5-fpm.sock
# Disable sendfile in Nginx to avoid VirtualBox synced directory bug.
set /files/etc/nginx/nginx.conf/http/sendfile off
save
"
echo "$changes" | augtool

# Install the Nginx site.
cp /vagrant/vagrant/nginx /etc/nginx/sites-available/wellrested
if [ ! -h /etc/nginx/sites-enabled/wellrested ] ; then
  ln -s /etc/nginx/sites-available/wellrested /etc/nginx/sites-enabled/wellrested
fi
if [ -h /etc/nginx/sites-enabled/default ] ; then
  rm /etc/nginx/sites-enabled/default
fi

# Create the document and symlinks.
if [ ! -d  /vagrant/htdocs ] ; then
  mkdir /vagrant/htdocs
fi
if [ ! -h /vagrant/htdocs/docs ] ; then
  ln -s /vagrant/docs/build/html /vagrant/htdocs/docs
fi
if [ ! -h /vagrant/htdocs/coverage ] ; then
  ln -s /vagrant/report /vagrant/htdocs/coverage
fi
if [ ! -f /vagrant/htdocs/index.php ] ; then
  cp /vagrant/vagrant/index.php /vagrant/htdocs/index.php
fi
if [ ! -d  /vagrant/autoload ] ; then
  mkdir /vagrant/autoload
fi

# Install Composer dependencies
composer --working-dir=/vagrant install

# Restart services.
service php5-fpm restart
service nginx restart

# Drop the user into the /vagrant directory on log in and dislay a message.
if ! grep /home/vagrant/.bashrc -e "cd /vagrant" &> /dev/null ; then
  echo "cd /vagrant" >> /home/vagrant/.bashrc
  echo "cat /vagrant/vagrant/log-in-message.txt" >> /home/vagrant/.bashrc
fi
