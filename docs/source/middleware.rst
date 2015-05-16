Middleware
==========

Okay, so what exactly **is** middleware? It's a nebulous term, and it's a bit reminscent of the Underpants gnomes.

- Phase 1: Request
- Phase 2: ???
- Phase 3: Respose

Middleware is indeed Phase 2. It's something (a callable or object) that takes a request and a response as inputs, does something with the response, and sends the altered response back out.

A Web service can built from many, many pieces of middleware, with each piece managing a specific task such as authentication or parsing representations. When each middleware runs, it is responsible for propagating the request through to the next middleware in the sequence—or deciding not to.

So what's it look like? In essence, a single piece of middleware looks something like this:

.. code-block:: php

    function ($request, $response, $next) {

        // Update the response.
        /* $response = ... */

        // Determine if any other middleware should be called after this.
        if (/* Stop now without calling more middleware? */) {
            // Return the response without calling any other middleware.
            return $response;
        }

        // Let the next middleware work on the response. This propagates "up"
        // the chain of middleware, and will eventually return a response.
        $response = $next($request, $response);

        // Possibly update the response some more.
        /* $response = ... */

        // Return the response.
        return $response;

    }

Defining Middleware
^^^^^^^^^^^^^^^^^^^

Middleware can be a callable (as in the `Getting Started`_) or an implementation of the ``WellRESTed\MiddlewareInterface`` (which implements ``__invoke`` so is technically a callable, too).

.. rubric:: Callable

.. code-block:: php

    /**
     * @param Psr\Http\Message\ServerRequestInterface $request
     * @param Psr\Http\Message\ResponseInterface $response
     * @param callable $next
     * @return Psr\Http\Message\ResponseInterface
     */
    function ($request, $response, $next) { }

.. rubric:: MiddlewareInterface

.. code-block:: php

    <?php

    namespace WellRESTed;

    use Psr\Http\Message\ResponseInterface;
    use Psr\Http\Message\ServerRequestInterface;

    interface MiddlewareInterface
    {
        /**
         * @param ServerRequestInterface $request
         * @param ResponseInterface $response
         * @param callable $next
         * @return ResponseInterface
         */
        public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next);
    }

Using Middleware
^^^^^^^^^^^^^^^^

Methods that accept middleware (e.g., ``Server::add``, ``Router::register``) allow you to provide middleware in a number of ways. For example, when you can provide a callable, a string containing a class name, an instance, or even an array containing a sequence of middleware.

Fully Qualified Class Name (FQCN)
---------------------------------

Assume your Web service has an autoloadable class named ``Webservice\Widgets\WidgetHandler``. You can register it with a router by passing a string containing the fully qualified class name (FQCN):

.. code-block:: php

    $router->register("GET,PUT,DELETE", "/widgets/{id}", 'Webservice\Widgets\WidgetHandler');

The class is not loaded, and no instances are created, until the route is matched and dispatched. Even for a router with 100 routes, no middleware registered by string name is loaded, except for the one that matches the request.

Callable Provider
-----------------

You can also use a callable to instantiate and return a ``MiddlewareInterface`` instance or middleware callable.

.. code-block:: php

    $router->add("GET,PUT,DELETE", "/widgets/{id}", function () {
        return new \Webservice\Widgets\WidgetHandler();
    });

This still delays instantiation, but gives you some added flexibility. For example, you could define middleware that receives some configuration upon construction.

.. code-block:: php

    $container = new MySuperCoolDependencyContainer();

    $router->add("GET,PUT,DELETE", "/widgets/{id}", function ()  use ($container) {
        return new \Webservice\Widgets\WidgetHandler($container);
    });

This is one approach to `dependency injection`_.

Middleware Callable
-------------------

Use a middleware callable directly.

.. code-block:: php

    $router->add("GET,PUT,DELETE", "/widgets/{id}", function ($request, $response, $next) {
        $response = $response->withStatus(200)
            ->withHeader("Content-type", "text/plain")
            ->withBody(new \WellRESTed\Message\Stream("It's a bunch of widgets!");
        return $next($request, $response);
    });

Instance
--------

You can also provide pass an instnace directly as middleware.

.. code-block:: php

    $router->add("GET,PUT,DELETE", "/widgets/{id}", new \Webservice\Widgets\WidgetHandler());

.. warning::

    This is simple, but has a significant disadvantage over the other options because each middleware used this way will be loaded and instantiated, even though only one middleware will actually be used for a given request-response cycle. You may find this approach useful for testing, but avoid if for production code.

Array
-----

Why use one middleware when you can use more?

Provide a sequence of middleware as an array. Each component of the array can be any of the varieties listed in this section.

When disptached, the middleware in the array will run in order, with each calling the one following via the ``$next`` parameter.

.. code-block:: php

    $router->add("GET", "/widgets/{id}", ['Webservice\Auth', $jsonParser, $widgetHandler]);

Chaining Middleware
^^^^^^^^^^^^^^^^^^^

Chaining middleware together allows you to build your Web service in a discrete, modular pieces. Each middleware in the chain makes the decision to either move the request up the chain by calling ``$next``, or stop propagation by returning a response without calling ``$next``.

Propagating Up the Chain
------------------------

Imagine we want to add authorization to the ``/widgets/{id}`` endpoint. We can do this without altering the existing middleware that deals with the widget itself.

What we do is create an additional middleware that performs just the authorization task. This middleware will inspect the incoming request for authorization headers, and either move the request on up the chain to the next middleware if all looks good, or send a request back out with an appropriate status code.

Here's an example authorization middleware using pseudocode.

.. code-block:: php

    namespace Webserice;

    class Authorization implements \WellRESTed\MiddlewareInterface
    {
        public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next)
        {
            // Validate the headers in the request.
            try {
                $validateUser($request);
            } catch (InvalidHeaderException $e) {
                // User did not supply the right headers.
                // Respond with a 401 Unauthorized status.
                return $response->withStatus(401);
            } catch (BadUserException $e) {
                // User is not permitted to access this resource.
                // Respond with a 403 Forbidden status.
                return $response->withStatus(403);
            }

            // No exception was thrown, so propagate to the next middleware.
            return $next($request, $response);
        }
    }

We can add authorization for just the ``/widgets/{id}`` endpoint like this:

.. code-block:: php

    $router->register("GET,PUT,DELETE",  "/widgets/{id}", [
            'Webservice\Auhtorizaiton',
            'Webservice\Widgets\WidgetHandler'
        ]);

Or, if you wanted to use the authorization for the entire service, you can add it to the ``Server`` in front of the ``Router``.

 .. code-block:: php

    $server = new \WellRESTed\Server();
    $server
        ->add('Webservice\Auhtorizaiton')
        ->add($server->createRouter()
            ->register("GET,PUT,DELETE", "/widgets/{id}", 'Webservice\Widgets\WidgetHandler')
        )
        ->respond();

Moving Back Down the Chain
--------------------------

The authorization example returned ``$next($request, $response)`` immidiately, but you can do some interesting things by working with the response that comes back from ``$next``. Think of the request as taking a round trip on the subway with each middleware being a stop along the way. Each of the  stops you go through going up the chain, you also go through on the way back down.

We could add a caching middleware in front of ``GET`` requests for a specific widget. This middleware will check if a cached representation exists for the resource the client requested. If it exists, it will send it out to the client without ever bothering the ``WidgetHandler``. If there's no representation cached, it will call ``$next`` to propgate the request up the chain. On the return trip (when the call to ``$next`` finishes), the caching middleware will inspect the response and store the body to the cache for next time.

Here's a pseudocode example:

.. code-block:: php

    namespace Webserice;

    class Cache implements \WellRESTed\MiddlewareInterface
    {
        public function dispatch(ServerRequestInterface $request, ResponseInterface $response, $next)
        {
            // Inspect the request path to see if there is a representation on
            // hand for this resource.
            $representation = $this->getCachedRepresentation($request);
            if ($representation !== null) {
                // There is already a cached representation. Send it out
                // without propagating.
                return $reponse
                    ->withStatus(200)
                    ->withBody($representation);
            }

            // No representation exists. Propagate to the next middleware.
            $response = $next($request, $response);

            // Attempt to store the response to the cache.
            $this->storeRepresentationToCache($response);

            return $response;
        }

        private function getCachedRepresentation(ServerRequestInterface $request)
        {
            // Look for a cached representation. Return null if not found.
            // ...
        }

        private function storeRepresentationToCache(ResponseInterface $response)
        {
            // Ensure the response contains a success code, a valid body,
            // headers that allow caching, etc. and store the represetnation.
            // ...
        }
    }

We can add this caching middleware in the chain between the authorization middleware and the Widget.

.. code-block:: php

    $router->register("GET,PUT,DELETE",  "/widgets/{id}", [
            'Webservice\Auhtorizaiton',
            'Webservice\Cache',
            'Webservice\Widgets\WidgetHandler'
        ]);

Or, if you wanted to use the authorization and caching middelware for the entire service, you can add them to the ``Server`` in front of the ``Router``.

.. code-block:: php

    $server = new \WellRESTed\Server();
    $server
        ->add('Webservice\Auhtorizaiton')
        ->add('Webservice\Cache')
        ->add($server->createRouter()
            ->register("GET,PUT,DELETE", "/widgets/{id}", 'Webservice\Widgets\WidgetHandler')
        )
        ->respond();

.. _Dependency Injection: dependency-injection.html
.. _Getting Started: getting-started.html
