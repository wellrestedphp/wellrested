Getting Started
===============

This page provides a brief introduction to WellRESTed. We'll take a tour of some of the features of WellRESTed without getting into too much depth.

To start, we'll make a "`Hello, world!`_" to demonstrate the concepts of middleware and routing and show how to read variables from the request path.

Hello, World!
^^^^^^^^^^^^^

Let's start with a very basic "Hello, world!". Here, we will create a server. A ``WellRESTed\Server`` reads the
incoming request from the client, dispatches some middleware_, and transmits a response back to the client.

Our middleware is a function that returns a response with the status code set to ``200`` and the body set to "Hello, world!".

.. _`Example 1`:
.. rubric:: Example 1: Simple "Hello, world!"

.. code-block:: php

    <?php

    use WellRESTed\Message\Stream;
    use WellRESTed\Server;

    require_once "vendor/autoload.php";

    // Create a new server.
    $server = new Server();

    // Add middleware to dispatch that will return a response.
    // In this case, we'll use an anonymous function.
    $server->add(function ($request, $response, $next) {
        // Update the response with the greeting, status, and content-type.
        $response = $response->withStatus(200)
            ->withHeader("Content-type", "text/plain")
            ->withBody(new Stream("Hello, world!"));
        // Use $next to forward the request on to the next middleware, if any.
        return $next($request, $response);
    });

    // Read the request sent to the server and use it to output a response.
    $server->respond();

.. note::

    The middleware in this example provides a ``Stream`` as the body instead of a string. This is a feature or PSR-7 where HTTP message bodies are always represented by streams. This allows you to work with very large bodies without having to store the entire contents in memory.

    WellRESTed provides ``Stream`` and ``NullStream``, but you can use any implementation of ``Psr\Http\Message\StreamInterface``.

Routing by Path
^^^^^^^^^^^^^^^

This is a good start, but it provides the same response to every request. Let's provide this response only when a client sends a request to ``/hello``.

For this, we need a router_. A router_ is a special type of middleware_ that examines the request and routes the request through to the middleware that matches.

.. _`Example 2`:
.. rubric:: Example 2: Routed "Hello, world!"

.. code-block:: php

    <?php

    use WellRESTed\Message\Stream;
    use WellRESTed\Server;

    require_once "vendor/autoload.php";

    // Create a new server and use it to create a new router.
    $server = new Server();
    $router = $server->createRouter();

    // Map middleware to an endpoint and method(s).
    $router->register("GET", "/hello", function ($request, $response, $next) {
        // Update the response with the greeting, status, and content-type.
        $response = $response->withStatus(200)
            ->withHeader("Content-type", "text/plain")
            ->withBody(new Stream("Hello, world!"));
        // Use $next to forward the request on to the next middleware, if any.
        return $next($request, $response);
    });

    // Add the router to the server.
    $server->add($router);

    // Read the request sent to the server and use it to output a response.
    $server->respond();

Reading Path Variables
^^^^^^^^^^^^^^^^^^^^^^

Routes can be static (like the one above that matches only ``/hello``), or they can be dynamic. Here's an example that uses a dynamic route to read a portion from the path to use as the greeting. For example, a request to ``/hello/Molly`` will respond "Hello, Molly", while a request to ``/hello/Oscar`` will respond "Hello, Oscar!"



.. _`Example 3`:
.. rubric:: Example 3: Personalized "Hello, world!"

.. code-block:: php

    <?php

    use WellRESTed\Message\Stream;
    use WellRESTed\Server;

    require_once "vendor/autoload.php";

    // Define middleware.
    $hello = function ($request, $response, $next) {

        // Check for a "name" attribute which may have been provided as a
        // path variable. The second parameters allows us to set a default.
        $name = $request->getAttribute("name", "world");

        // Update the response with the greeting, status, and content-type.
        $response = $response->withStatus(200)
            ->withHeader("Content-type", "text/plain")
            ->withBody(new Stream("Hello, $name!"));

        return $next($request, $response);
    }

    // Create the server and router.
    $server = new Server();
    $router = $server->createRouter();

    // Register the middleware for an exact match to /hello
    $router->register("GET", "/hello", $hello);
    // Register to match a pattern with a variable.
    $router->register("GET", "/hello/{name}", $hello);

    $server->add($router);
    $server->respond();

Multiple Middleware
^^^^^^^^^^^^^^^^^^^

One thing we haven't seen yet is how middleware work together. For the next example, we'll use an additional middleware that sets an ``X-example: hello world``.

.. code-block:: php

    <?php

    use WellRESTed\Message\Stream;
    use WellRESTed\Server;

    require_once "vendor/autoload.php";

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

    // Add $headerAdder to the server first to make it the first to run.
    $server->add($headerAdder);

    // When $headerAdder calls $next, it will dispatch the router because it is
    // added to the server right after.
    $server->add($server->createRouter()
        ->register("GET", "/hello", $hello)
        ->register("GET", "/hello/{name}", $hello)
    );

    // Read the request from the client, dispatch middleware, and output.
    $server->respond();


.. _middleware: middleware.html
.. _router: router.html
