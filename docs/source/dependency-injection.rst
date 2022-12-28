Dependency Injection
====================

While WellRESTed does not provide its own dependency injection container, it does support the PSR-11_ standard used by various libraries such as PHP-DI_.

To configure your WellRESTed ``Server`` with a dependency container, call ``Server::setContainer``. Then, register handlers and middleware using the service names (usually, these will be the fully qualified class names).

An example using PHP-DI_ looks like this:

.. code-block:: php

    $builder = new DI\ContainerBuilder();
    $builder->addDefinitions([

        Server::class => function (DI\Container $c): Server {

            $server = new Server();
            // Pass the reference to the container.
            $server->setContainer($c);

            $router = $server->createRouter();
            $server->add($router);

            // Register handlers and middleware using service names.
            $router->register('GET,POST', '/cats/', CatsHandler::class);
            $router->register('GET', '/cats/{id}', GetCatHandler::class);
            $router->register('GET', '/dogs/', 'dogs.list.handler');

            return $server;
        },

        // This handler is configured using a specific name instead of FQCN.
        'dogs.list.handler' => DI\autowire(DogsHandler::class),

    ]);

When the server receives a request that matches a route, it will resolve the needed handler from the dependency container along with any dependencies. The server will not instantiate anything else.

When registering routes, be sure to pass the service name as a string. **Do not resolve the service**. This will instantiate the handler for **every** request, even when it is not needed.

.. code-block:: php

    // Correct. Provides the the service name.
    $router->register('GET,POST', '/cats/', CatsHandler::class);

    // Avoid! Resolving the handler here Will instatiate the handler for
    // every request, even when it is not needed
    $router->register('GET,POST', '/cats/', $c->get(CatsHandler::class))

.. _PSR-11: https://www.php-fig.org/psr/psr-11/
.. _PHP-DI: https://php-di.org/
