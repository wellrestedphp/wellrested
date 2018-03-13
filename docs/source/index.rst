WellRESTed
==========

WellRESTed is a library for creating RESTful APIs and websites in PHP that provides abstraction for HTTP messages, a powerful handler and middleware system, and a flexible router.

Features
--------

PSR-7 HTTP Messages
^^^^^^^^^^^^^^^^^^^

Request and response messages are built to the interfaces standardized by PSR-7_ making it easy to share code and use components from other libraries and frameworks.

The message abstractions facilitate working with message headers, status codes, variables extracted from the path, message bodies, and all the other aspects of requests and responses.

PSR-15 Handler interfaces
^^^^^^^^^^^^^^^^^^^^^^^^^

Handlers and middleware may implement the interfaces define by the PSR-15_ standard.

Router
^^^^^^

The router_ allows you to define your endpoints using `URI Templates`_ like ``/foo/{bar}/{baz}`` that match patterns of paths and provide captured variables. You can also match exact paths for extra speed or regular expressions for extra flexibility.

WellRESTed's router automates responding to ``OPTIONS`` requests for each endpoint based on the methods you assign. ``405 Method Not Allowed`` responses come free of charge as well for any methods you have not implemented on a given endpoint.

Middleware
^^^^^^^^^^

The middleware_ system allows you to build your Web service out of discrete, modular pieces. These pieces can be run in sequences where each has a chance to modify the response before handing it off to the next. For example, an authenticator can validate a request and forward it to a cache; the cache can check for a stored representation and forward to another middleware if no cached representation is found, etc. All of this happens without any one middleware needing to know anything about where it is in the chain or which middleware comes before or after.

Most middleware is never autoloaded or instantiated until it is needed, so a Web service with hundreds of middleware still only creates instances required for the current request-response cycle.

You can register middleware directly, register callables that return middleware (e.g., dependency container services), or register strings containing the middleware class names to autoload and instantiate on demand.



Extensible
^^^^^^^^^^

All classes are coded to interfaces to allow you to provide your own implementations and use them in place of the built-in classes. For example, if your Web service needs to be able to dispatch middleware that implements a different interface, you can provide your own custom ``DispatcherInterface`` implementation.

Example
-------

Here's a customary "Hello, world!" example. This site will respond to requests for ``GET /hello`` with "Hello, world!" and provide custom responses for other paths (e.g., ``GET /hello/Molly`` will respond "Hello, Molly!").

The site will also provide an ``X-example: hello world`` using dedicated middleware, just to illustrate how middleware propagates.

.. code-block:: php

    <?php

    use Psr\Http\Message\ResponseInterface;
    use Psr\Http\Message\ServerRequestInterface;
    use Psr\Http\Server\MiddlewareInterface;
    use Psr\Http\Server\RequestHandlerInterface;
    use WellRESTed\Message\Response;
    use WellRESTed\Message\Stream;
    use WellRESTed\Server;

    require_once 'vendor/autoload.php';

    // Create a handler that will construct and return a response. We'll 
    // register this handler with a server and router below.
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

    // Create middleware that will add a custom header to every response.
    class CustomerHeaderMiddleware implements MiddlewareInterface
    {
        public function process(
            ServerRequestInterface $request,
            RequestHandlerInterface $handler
        ): ResponseInterface {

            // Delegate to the next handler in the chain to obtain a response.
            $response = $handler->handle($request);

            // Add the header.
            $response = $response->withHeader("X-example", "hello world");

            // Return the altered response.
            return $response;
        }
    }

    // Create a server
    $server = new Server();

    // Add the header adding middleware to the server first so that it will
    // forward requests on to the router.
    $server->add(new CustomerHeaderMiddleware());

    // Create a router to map methods and endpoints to handlers.
    $router = $server->createRouter();

    $handler = new HelloHandler();
    // Register a route to the handler without a variable in the path.
    $router->register('GET', '/hello', $handler);
    // Register a route that reads a "name" from the path.
    // This will make the "name" request attribute available to the handler.
    $router->register('GET', '/hello/{name}', $handler);
    $server->add($router);

    // Read the request from the client, dispatch, and output.
    $server->respond();

Contents
--------

.. toctree::
   :maxdepth: 4

   overview
   getting-started
   messages
   handlers-and-middleware
   router
   uri-templates
   uri-templates-advanced
   extending
   dependency-injection
   additional
   web-server-configuration

.. _PSR-7: http://www.php-fig.org/psr/psr-7/
.. _PSR-15: http://www.php-fig.org/psr/psr-15/
.. _router: router.html
.. _URI Templates: uri-templates.html
