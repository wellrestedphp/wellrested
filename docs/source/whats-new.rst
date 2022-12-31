What's New in Version 6.0?
==========================

Minimum PHP Version of 8.1
--------------------------

For version 6, we increased the minimum PHP version from 7.3 to 8.1. With 7.4 having reached end of life several months ago, it was an easy decision to bump the minimum version to 8.x. We went with 8.1 in order to make use of some of the new features such as enumerations.

Dependency Injection with PSR-11
--------------------------------

WellRESTed now integrates directly with any PSR-11_ dependency container. This makes registering handlers and middleware much more straight forward. To use this feature, install the dependency container of your choice that implements PSR-11_ such as PHP-DI_.

Using the dependency container involves these steps:

#. Configure the ``Server`` by passing the container to ``Server::setContainer``.
#. Register handlers and middleware by passing the service name. This is usually the fully qualified class name,

Here's an example using PHP-DI_.

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

Using the dependency container is an optional, but highly recommended feature. We encourage all projects using WellRESTed to adopt this approach.

However, if you're not using a PSR-11_ container or are unable to migrate to one easily, all of the previous methods of registering handlers and middleware still work the same as they have in previous versions. The next best approach is to register handlers as factory functions (callables that return handlers).

Trailing Slash Mode
-------------------

New in version 6.0, you can customize how WellRESTed acts when a route would match if the request had a trailing slash appended. Configure the mode by calling ``Server::setTrailingSlashMode()`` and passing a ``TrailingSlashMode`` enumeration.

Assuming a site has a ``/cats/`` (with slash) route. When a client sends a request for ``GET /cats`` (no slash), the modes will yields these results:

* **STRICT**: Route will fail to match. This is the default mode and is consistent with how previous versions of WellRESTed behave.
* **LOOSE**: Route will match as if the original request were for ``/cats/``.
* **REDIRECT**: Will respond with 301 redirect with ``/cats/`` as the ``Location`` header.

Note that these modes work similarly when the route is registered without a trailing slash and the request provides one.

Configuration
-------------

With the addition of configuring the DI container and trailing slash mode, there's more to configure on the ``Server`` than in previous versions. Previously, sites using WellRESTed had to be careful to set certain configurations before calling ``Server::createRouter``. Version 6 eliminates the temporal couplings around configuration by providing weak references to the ``Server``. This should avoid some gotchas and edge cases where methods had to be called in particular orders.

.. _PSR-11: https://www.php-fig.org/psr/psr-11/
.. _PHP-DI: https://php-di.org/
