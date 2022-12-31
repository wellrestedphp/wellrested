What's New in Version 6.0?
==========================

Minimum PHP Version of 8.1
--------------------------

For version 6, we increased the minimum PHP version from 7.3 to 8.1. With 7.4 having reached end of life several months ago, it was an easy decision to bump the minimum version to 8.x. We went with 8.1 in order to make use of some of the new features such as enumerations.

Dependency Injection with PSR-11
--------------------------------

WellRESTed now integrates directly with any PSR-11_ dependency container. This makes configuring WellRESTed much more straight forward. To use this feature, install the dependency container of your choice that implements PSR-11_ such as PHP-DI_. WellRESTed will resolve the handler using the string name when it's about to use it to handle the request.

Using the dependency container involves these steps:

#. Configure the ``Server`` by passing the container to ``Server::setContainer``.
#. Register handlers and middleware by passing the service name. This is usually the fully qualified class name,

Here's an example:

.. code-block:: php

    $builder = new DI\ContainerBuilder();
    $builder->addDefinitions([

        Server::class => function (ContainerInterface $c): Server {

            $server = new Server();
            // Pass the reference to the container.
            $server->setContainer($c);

            $router = $server->createRouter();
            $server->add($router);

            // Register handlers and middleware using service names.
            $router->register('GET,POST', '/cats/', CatsHandler::class);
            $router->register('GET', '/cats/{id}', GetCatHandler::class);

            return $server;
        }

    ]);

Using the dependency container is an optional, but highly recommended feature. We encourage all projects using WellRESTed to convert to this is possible. However, if you're not using this, all of the previous methods of registering handlers still work fine, and the next best approach to this is to register handlers as factory functions (callables that return handlers).

Trailing Slash Mode
-------------------

New in version 6.0, you can customize how WellRESTed acts when a route would match if the request had a trailing slash appeneded. Configure the mode by calling ``Server->setTrailingSlashMode()`` and passing a ``TralilingSlashMode`` enumeration.

Assuming a site has a ``/cats/`` route. When a client sends a ``GET /cats/`` request, the modes will yields these results:

* **``STRICT``**: Route will fail to match. This is the default mode and is consistent with how provious versions of WellRESTed behave.
* **``LOOSE`**: Route will match as if the original request were for ``/cats/``.
* **``REDIRECT`**: Will respond with 301 redirect with ``/cats/`` as the ``Location`` header.

Configuration
-------------

With the addition of configuring the DI container and trailing slash mode, there's more to configure on the ``Server`` then ever before. Previously, sites using WellRESTed had to be careful to set certain configurations before calling ``Server::createRouter``. Version 6 eliminitates the temporal couplings around configuration by providing weak references to the ``Server``.

.. _PSR-11: https://www.php-fig.org/psr/psr-11/
.. _PHP-DI: https://php-di.org/
