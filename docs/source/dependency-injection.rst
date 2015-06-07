Dependency Injection
====================

Here are a few strategies for how to make a dependency injection container available to middleware with WellRESTed.

Request Attribute
^^^^^^^^^^^^^^^^^

``Psr\Http\Message\ServerRequestInterface`` provides "attributes" that allow you attach arbitrary data to a request. You can use this to make your dependency container available to any dispatched middleware.

When you instantiate a ``WellRESTed\Server``, you can provide an array of attributes that the server will add to the request.

.. code-block:: php

    $container = new MySuperCoolDependencyContainer();

    $server = new WellRESTed\Server(["container" => $container]);
    // ... Add middleware, routes, etc. ...

When the server dispatches middleware, the middleware will be able to read the contain as the "container" attribute.

.. code-block:: php

    function ($request, $response, $next) {
        $container = $request->getAttribute("container");
        // It's a super cool dependency container!
    }

Callables
^^^^^^^^^

Another approach is to use callables that return ``MiddlewareInterface`` instances when you assign middleware. This approach provides an opportunity to pass the container into the middleware's constructor.


.. code-block:: php

    Class CatHandler implements WellRESTed\MiddlewareInterface
    {
        private $container;

        public function __construct($container)
        {
            $this->container = $container;
        }

        public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next);
        {
            // Do something with the $this->container, and make a response.
            // ...
            return $response;
        }
    }

When you add the middleware to the server or register it with a router, use a callable that passes container into the constructor.

.. code-block:: php

    $container = new MySuperCoolDependencyContainer();

    $catHandler = function () use ($container) {
        return new CatHandler($container);
    }

    $server = new Server();
    $server->add(
        $server->createRoute()
            ->register("GET", "/cats/{cat}", $catHandler)
        );
    $server->respond();

For extra fun, store the callable that provides the handler in the container. Here's an example using Pimple_).

.. code-block:: php

    $c = new Pimple\Container();
    $c["catHandler"] = $c->protect(function () use ($c) {
        return new CatHandler($c);
    });

    $server = new Server();
    $server->add(
        $server->createRoute()
            ->register("GET", "/cats/{cat}", $c["catHandler"])
        );
    $server->respond();

Combined
^^^^^^^^

Of course these two approaches are not mutually exclusive. You can even obtain your server from the container as well, for good measure.

.. code-block:: php

    $c = new Pimple\Container();
    $c["server"] = function ($c) {
        return new Server(["container" => $c);
    };
    $c["catHandler"] = $c->protect(function () use ($c) {
        return new CatHandler($c);
    });

    $server = $c["server"];
    $server->add(
        $server->createRoute()
            ->register("GET", "/cats/{cat}", $c["catHandler"])
        );
    $server->respond();

.. _Pimple: http://pimple.sensiolabs.org
