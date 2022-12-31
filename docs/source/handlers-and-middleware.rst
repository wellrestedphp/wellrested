Handlers and Middleware
=======================

WellRESTed allows you to define and use your handlers and middleware in a number of ways.

Defining Handlers and Middleware
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

PSR-15 Interfaces
-----------------

The preferred method is to use the interfaces standardized by PSR-15_. This standard includes two interfaces, ``Psr\Http\Server\RequestHandlerInterface`` and ``Psr\Http\Server\MiddlewareInterface``.

Use ``RequestHandlerInterface`` for individual components that generate and return responses.

.. code-block:: php

    class HelloHandler implements RequestHandlerInterface
    {
        public function handle(ServerRequestInterface $request): ResponseInterface
        {
            // Check for a "name" attribute which may have been provided as a
            // path variable. Use "world" as a default.
            $name = $request->getAttribute("name", "world");

            // Set the response body to the greeting and the status code to 200 OK.
            $response = (new Response(200))
                ->withHeader("Content-type", "text/plain")
                ->withBody(new Stream("Hello, $name!"));

            // Return the response.
            return $response;
        }
    }

Use ``MiddlewareInterface`` for classes that interact with other middleware and handlers. For example, you may have middleware that attempts to retrieve a cached response and delegates to other handlers on a cache miss.

.. code-block:: php

    class CacheMiddleware implements MiddlewareInterface
    {
        public function process(
            ServerRequestInterface $request,
            RequestHandlerInterface $handler
        ): ResponseInterface
        {

            // Inspect the request to see if there is a representation on hand.
            $representation = $this->getCachedRepresentation($request);
            if ($representation !== null) {
                // There is already a cached representation.
                // Return it without delegating to the next handler.
                return (new Response())
                    ->withStatus(200)
                    ->withBody($representation);
            }

            // No representation exists. Delegate to the next handler.
            $response = $handler->handle($request);

            // Attempt to store the response to the cache.
            $this->storeRepresentationToCache($response);

            return $response
        }

        private function getCachedRepresentation(ServerRequestInterface $request)
        {
            // Look for a cached representation. Return null if not found.
            // ...
        }

        private function storeRepresentationToCache(ResponseInterface $response)
        {
            // Ensure the response contains a success code, a valid body,
            // headers that allow caching, etc. and store the representation.
            // ...
        }
    }

Legacy Middleware Interface
---------------------------

Prior to PSR-15, WellRESTed's recommended handler interface was ``WellRESTed\MiddlewareInterface``. This interface is still supported for backwards compatibility.

This interface serves for both handlers and middleware. It differs from the ``Psr\Http\Server\MiddlewareInterface`` in that is expects an incoming ``$response`` parameter which you may use to generate the returned response. It also expected a ``$next`` parameter which is a ``callable`` with this signature:

.. code-block:: php

    function next($request, $response): ResponseInterface

Call ``$next`` and pass ``$request`` and ``$response`` to forward the request to the next handler. ``$next`` will return the response from the handler. Here's the cache example above as a ``WellRESTed\MiddlewareInterface``.

.. code-block:: php

    class CacheMiddleware implements WellRESTed\MiddlewareInterface
    {
        public function __invoke(
            ServerRequestInterface $request,
            ResponseInterface $response,
            $next
        ) {

            // Inspect the request to see if there is a representation on hand.
            $representation = $this->getCachedRepresentation($request);
            if ($representation !== null) {
                // There is already a cached representation.
                // Return it without delegating to the next handler.
                return $response
                    ->withStatus(200)
                    ->withBody($representation);
            }

            // No representation exists. Delegate to the next handler.
            $response = $next($request, $response);

            // Attempt to store the response to the cache.
            $this->storeRepresentationToCache($response);

            return $response
        }

        private function getCachedRepresentation(ServerRequestInterface $request)
        {
            // Look for a cached representation. Return null if not found.
            // ...
        }

        private function storeRepresentationToCache(ResponseInterface $response)
        {
            // Ensure the response contains a success code, a valid body,
            // headers that allow caching, etc. and store the representation.
            // ...
        }
    }

Callables
---------

You may also use a ``callable`` similar to the legacy ``WellRESTed\MiddlewareInterface``. The signature of the callable matches the signature of ``WellRESTed\MiddlewareInterface::__invoke``.

.. code-block:: php

    $handler = function ($request, $response, $next) {

        // Delegate to the next handler.
        $response = $next($request, $response);

        return $response
            ->withHeader("Content-type", "text/plain")
            ->withBody(new Stream("Hello, $name!"));
    }

Using Handlers and Middleware
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Methods that accept handlers and middleware (e.g., ``Server::add``, ``Router::register``, ``Router::add``) allow you to provide them in a number of ways. For example, you can provide an instance, a ``callable`` that returns an instance, or an ``array`` of middleware to use in sequence. The following examples demonstrate each of the ways you can register handlers and middleware.

Dependency Service Name (Recommended)
-------------------------------------

The recommonded approach is to use a :ref:`dependency injection` container and register the service by the service name. WellRESTed will resolve the handler needed for the current request automatically. No other handlers (and their dependencies) will be instantiated. See the section on :ref:`dependency injection` for more information.

.. code-block:: php

    // How you add the handler to the container varies by DI library.

    // PHP-DI looks like this:
    WidgetHandler::class => DI\autowire()

    // Pimple looks like:
    $container[WidgetHandler::class] = fn($c) => new WidgetHandler($c[MyDependency::class]);

    // In both examples, the service name for the handler is the FQCN.
    // Register the handler with the route using this service name.
    $router->register("GET,PUT,DELETE", "/widgets/{id}", WidgetHandler::class);

Factory Functions
-----------------

Prior to v6, using a function that returns an instance of your handler was the best approach. You can still use this if you're not using a DI container or if your DI container does not conform to PSR-11_.

.. code-block:: php

    $widgetFactory = function (): WidgetHandler {
        return new WidgetHandler(new MyDependency());
    }

    $router->register("GET,PUT,DELETE", "/widgets/{id}", $widgetFactory);

For sites using Pimple_, WellRESTed used to recomend using the ``protect`` feature to wrap the handlers in a factory function. Here's an example:

.. code-block:: php

    // Adding the handler to the container.
    $pimple[WidgetHandler::class] = $pimple->protect(
        function () use ($pimple): WidgetHandler {
            return new WidgetHandler($pimple[MyDependency::class]);
        }
    );

While you can still do this, consider using Pimple's `PSR-11 adapter`_ and follow the instructions under :ref:`Dependency Service Name (Recommended)`.

Fully Qualified Class Name (FQCN)
---------------------------------

For handlers that do not require any arguments passed to the constructor, you may pass the fully qualified class name of your handler as a ``string``. You can do that like this:

.. code-block:: php

    $router->register('GET,PUT,DELETE', '/widgets/{id}', App\WidgetHandler::class);
    // ... or ...
    $router->register('GET,PUT,DELETE', '/widgets/{id}', 'App\\WidgetHandler');

The class is not loaded, and no instances are created, until the route is matched and dispatched. However, the drawback to this approach is the there is no way to pass any arguments to the constructor.

.. note::

    When using :ref:`Dependency Injection`, WellRESTed will always attempt to resolve strings from the dependency container first. If no services exists with that name, or there is no container, WellRESTed will create an instance without passing constructor arguments.

Instance
--------

WellRESTed also allows you to pass an instance of a handler directly. This may be useful for smaller handlers that don't require many dependencies, although registering by service name or factory function is usually better.

.. code-block:: php

    $widgetHandler = new WidgetHandler(new MyDependency());

    $router->register("GET,PUT,DELETE", "/widgets/{id}", $widgetHandler);

.. warning::

    This is simple, but has a significant disadvantage over the other options because each handler used this way will be loaded and instantiated, even if it's not needed to handle the current request. You may find this approach useful for testing, but avoid if for production code.

Array
-----

The final approach is to provide a sequence of middleware and a handler as an ``array``.

For example, imagine if we have these services in the DI container:

* ``AuthMiddleware::class``: Locates the end user making the request
* ``CacheMiddleware::class``: Provides a cached response if able
* ``WidgetHandler::class``: Provides a widget representation

We could provide these as a sequence by using an ``array``.

.. code-block:: php

    $router->register('GET', '/widgets/{id}', [
        AuthMiddleware::class,
        CacheMiddleware::class,
        WidgetHandler::class
    ]);

.. _Dependency Injection: dependency-injection.html
.. _Pimple: https://pimple.symfony.com/
.. _PSR-11 Adapter: https://github.com/silexphp/Pimple#the-psr-11-container-class
.. _PSR-11: https://www.php-fig.org/psr/psr-11/
.. _PSR-15: https://www.php-fig.org/psr/psr-15/
