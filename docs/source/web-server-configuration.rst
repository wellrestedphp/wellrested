Web Server Configuration
========================

You will typically want to have all traffic on your site directed to a single script that creates a ``WellRESTed\Server`` and calls ``respond``. Here are basic setups for doing this in Nginx_ and Apache_.

Nginx
^^^^^

.. code-block:: nginx

    server {

        listen 80;
        server_name your.hostname.here;
        root /your/sites/document/root;
        index index.php index.html;
        charset utf-8;

        # Attempt to serve actual files first.
        # If no file exists, send to /index.php
        location / {
            try_files $uri $uri/ /index.php?$args;
        }

        location ~ \.php$ {
            try_files $uri =404;
            fastcgi_pass unix:/var/run/php5-fpm.sock;
            fastcgi_index index.php;
            include fastcgi_params;
        }

    }

Apache
^^^^^^

.. code-block:: apache

    RewriteEngine on
    RewriteBase /

    # Send all requests to non-regular files and directories to index.php
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^.+$ index.php [L,QSA]
