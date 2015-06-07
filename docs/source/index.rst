WellRESTed
==========

WellRESTed is a library for creating RESTful APIs and websites in PHP that provides abstraction for HTTP messages, a powerful middleware system, and a flexible router.

Features
--------

PSR-7 HTTP Messages
^^^^^^^^^^^^^^^^^^^

Request and response messages are built to the interfaces standardized by PSR-7_ making it easy to share code and use components from other libraries and frameworks.

The message abstractions facilitate working with message headers, status codes, variables extracted from the path, message bodies, and all the other aspects of requests and responses.

Middleware
^^^^^^^^^^

The middleware_ system allows you to map build sequences of modular code that propagate from one to the next. For example, an authenticator can validate a request and forward it to a cache; the cache can check for a stored representation and forward to another middleware if no cached representation is found, etc. All of this happens without any one middleware needing to know anything about where it is in the chain or which middleware comes before or after.

Most middleware is never autoloaded or instantiated until it is needed, so a Web service with hundreds of middleware still only creates instances required for the current request-respose cycle.

You can register middleware directly, register callables that return middleware (e.g., dependency container services), or register strings containing the middleware classnames to autoload and instantiate on demand.

Router
^^^^^^

The router_ allows you to define your endpoints using `URI Templates`_ like ``/foo/{bar}/{baz}`` that match patterns of paths and provide captured variables. You can also match exact paths for extra speed or regular expressions for extra flexibility.

WellRESTed's automates responding to ``OPTIONS`` requests for each endpoint based on the method you assign. ``405 Method Not Allowed`` come free of charge as well for any methods you have not implemented on a given endpoint.

Extensible
^^^^^^^^^^

All classes are coded to interfaces to allow you to provide your own implementations and use them in place of the built-in classes. For example, if your Web service needs to be able to dispatch middleware that implements a different interface, you can provide your own custom ``DispatcherInterface`` implentation.

Example
-------

Here's a customary "Hello, world!" example. This site will respond to requests for ``GET /hello`` with "Hello, world!" and provide custom responses for other paths (e.g., ``GET /hello/Molly`` will respond "Hello, Molly!").

The site will also provide an ``X-example: hello world`` using dedicated middleware, just to illustrate how middleware propagates.

.. code-block:: php

    <?php

    use WellRESTed\Message\Stream;
    use WellRESTed\Server;

    require_once "vendor/autoload.php";

    // Build some middleware. We'll register these with a server below.
    // We're using callables to fit this all in one example, but these
    // could also be classes implementing WellRESTed\MiddlewareInterface.

    // Set the status code and provide the greeting as the response body.
    $hello = function ($request, $response, $next) {

        // Check for a "name" attribute which may have been provided as a
        // path variable. Use "world" as a default.
        $name = $request->getAttribute("name", "world");

        // Set the response body to the greeting and the status code to 200 OK.
        $response = $response->withStatus(200)
            ->withHeader("Content-type", "text/plain")
            ->withBody(new Stream("Hello, $name!"));

        // Propagate to the next middleware, if any, and return the response.
        return $next($request, $response);

    };

    // Add a header to the response.
    $headerAdder = function ($request, $response, $next) {
        // Add the header.
        $response = $response->withHeader("X-example", "hello world");
        // Propagate to the next middleware, if any, and return the response.
        return $next($request, $response);
    };

    // Create a server
    $server = new Server();

    // Start each request-response cycle by dispatching the header adder.
    $server->add($headerAdder);

    // The header adder will propagate to this router, which will dispatch the
    // $hello middleware, possibly with a {name} variable.
    $server->add($server->createRouter()
        ->register("GET", "/hello", $hello)
        ->register("GET", "/hello/{name}", $hello)
    );

    // Read the request from the client, dispatch middleware, and output.
    $server->respond();

Contents
--------

.. toctree::
   :maxdepth: 4

   overview
   getting-started
   messages
   middleware
   router
   uri-templates
   uri-templates-advanced
   extending
   dependency-injection
   web-server-configuration

.. _PSR-7: http://www.php-fig.org/psr/psr-7/
.. _middleware: middleware.html
.. _router: router.html
.. _URI Templates: uri-templates.html
