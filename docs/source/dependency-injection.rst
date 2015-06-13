Dependency Injection
====================

WellRESTed strives to play nicely with other code and not force developers into using any specific libraries or frameworks. As such, WellRESTed does not provide a dependency injection container, nor does it require you to use a specific container (or any).

This section describes a handful of ways of making dependencies available to middleware.

Request Attribute
^^^^^^^^^^^^^^^^^

``Psr\Http\Message\ServerRequestInterface`` provides "attributes" that allow you attach arbitrary data to a request. You can use this to make your dependency container available to any dispatched middleware.

When you instantiate a ``WellRESTed\Server``, you can provide an array of attributes that the server will add to the request.

.. code-block:: php

    $container = new MySuperCoolDependencyContainer();

    $server = new WellRESTed\Server(["container" => $container]);
    // ... Add middleware, routes, etc. ...

When the server dispatches middleware, the middleware will be able to read the container as the "container" attribute.

.. code-block:: php

    function ($request, $response, $next) {
        $container = $request->getAttribute("container");
        // It's a super cool dependency container!
    }

.. note::

    This approach is technically more of a `service locator`_ pattern. It's easy to implement, and it allows you the most flexibility in how you assign middleware.

    It has some drawbacks as well, though. For example, your middleware is now dependent on your container, and describing which items needs to be **in** the container provides its own challenge.

    If your interested in a truer dependency injection approach, read on to the next section where we look at registering middleware factories.

Middleware Factories
^^^^^^^^^^^^^^^^^^^^

Another approach is to use a factory function that returns middleware, usually in the form of a ``MiddlewareInterface`` instance. This approach provides the opportunity to pass dependencies to your middleware's constructor, while still delaying instantiation until the middleware is used.

Imagine a middleware ``FooHandler`` that depends on a ``BarInterface``, and ``BazInterface``.

.. code-block:: php

    Class FooHandler implements WellRESTed\MiddlewareInterface
    {
        private $bar;
        private $baz;

        public function __construct(BarInterface $bar, BazInterface $bar)
        {
            $this->bar = $bar;
            $this->baz = $baz;
        }

        public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next);
        {
            // Do something with the bar and baz and update the response.
            // ...
            return $response;
        }
    }

When you add the middleware to the server or register it with a router, you can use a callable that passes appropriate instances into the constructor.

.. code-block:: php

    // Assume $bar and $baz exist in this scope.
    $fooHandlerFactory = function () use ($bar, $bar) {
        return new FooHandler($bar, $baz);
    }

    $server = new Server();
    $server->add(
        $server->createRoute()
            ->register("GET", "/foo/{id}", $fooHandlerFactory)
        );
    $server->respond();

You can combine this approach with a dependency container. Here's an example using Pimple_).

.. code-block:: php

    $c = new Pimple\Container();
    $c["bar"] = /* Return a BarInterface */
    $c["baz"] = /* Return a BazInterface */
    $c["fooHandler"] = $c->protect(function () use ($c) {
        return new FooHandler($c["bar"], $c["baz"]);
    });

    $server = new Server();
    $server->add(
        $server->createRoute()
            ->register("GET", "/foo/{id}", $c["fooHandler"])
        );
    $server->respond();

.. _Pimple: http://pimple.sensiolabs.org
.. _service locator: https://en.wikipedia.org/wiki/Service_locator_pattern
