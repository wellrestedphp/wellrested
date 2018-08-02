Getting Started
===============

This page provides a brief introduction to WellRESTed. We'll take a tour of some of the features of WellRESTed without getting into too much depth.

To start, we'll make a "Hello, world!" to demonstrate the concepts of handlers and routing and show how to read variables from the request path.

Hello, World!
^^^^^^^^^^^^^

Let's start with a very basic "Hello, world!" Here, we will create a server. A ``WellRESTed\Server`` reads the incoming request from the client, dispatches a handler, and transmits a response back to the client.

Our handler will create and return a response with the status code set to ``200`` and the body set to "Hello, world!".

.. _`Example 1`:
.. rubric:: Example 1: Simple "Hello, world!"

.. code-block:: php

    <?php

    use Psr\Http\Server\RequestHandlerInterface;
    use WellRESTed\Message\Response;
    use WellRESTed\Message\Stream;
    use WellRESTed\Server;

    require_once 'vendor/autoload.php';

    // Define a handler implementing the PSR-15 RequestHandlerInterface interface.
    class HelloHandler implements RequestHandlerInterface
    {
        public function handle(ServerRequestInterface $request): ResponseInterface
        {
            $response = (new Response(200))
                ->withHeader('Content-type', 'text/plain')
                ->withBody(new Stream('Hello, world!'));
            return $response;
        }
    }

    // Create a new server.
    $server = new Server();

    // Add this handler to the server.
    $server->add(new HelloHandler());

    // Read the request sent to the server and use it to output a response.
    $server->respond();

.. note::

    The handler in this example provides a ``Stream`` as the body instead of a string. This is a feature or PSR-7 where HTTP message bodies are always represented by streams. This allows you to work with very large bodies without having to store the entire contents in memory.

    WellRESTed provides ``Stream`` and ``NullStream``, but you can use any implementation of ``Psr\Http\Message\StreamInterface``.

Routing by Path
^^^^^^^^^^^^^^^

This is a good start, but it provides the same response to every request. Let's provide this response only when a client sends a request to ``/hello``.

For this, we need a router_. A router_ examines the request and sends the request through to the handler that matches the request's HTTP method and path.

.. _`Example 2`:
.. rubric:: Example 2: Routed "Hello, world!"

.. code-block:: php

    // Create a new server.
    $server = new Server();

    // Create a router to map methods and endpoints to handlers.
    $router = $server->createRouter();
    $router->register('GET', '/hello', new HelloHandler());
    $server->add($router);

    // Read the request sent to the server and use it to output a response.
    $server->respond();

Reading Path Variables
^^^^^^^^^^^^^^^^^^^^^^

Routes can be static (like the one above that matches only ``/hello``), or they can be dynamic. Here's an example that uses a dynamic route to read a portion from the path to use as the greeting. For example, a request to ``/hello/Molly`` will respond "Hello, Molly", while a request to ``/hello/Oscar`` will respond "Hello, Oscar!"

.. _`Example 3`:
.. rubric:: Example 3: Personalized "Hello, world!"

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

    // Create the server and router.
    $server = new Server();
    $router = $server->createRouter();

    // Register the middleware for an exact match to /hello
    $router->register("GET", "/hello", $hello);
    // Register to match a pattern with a variable.
    $router->register("GET", "/hello/{name}", $hello);
    $server->add($router);

    $server->respond();

Middleware
^^^^^^^^^^

In addition to handlers, which provide responses directly, WellRESTed also supports middleware to act on the requests and then pass them on for other middleware or handlers to work with.

Middleware allows you to compose your application in multiple pieces. In the example, we'll use middleware to add a header to every response, regardless of which handler is called.

.. code-block:: php

    // This middleware will add a custom header to every response.
    class CustomHeaderMiddleware implements MiddlewareInterface
    {
        public function process(
            ServerRequestInterface $request,
            RequestHandlerInterface $handler
        ): ResponseInterface {

            // Delegate to the next handler in the chain to obtain a response.
            $response = $handler->handle($request);

            // Add the header to the response we got back from upstream.
            $response = $response->withHeader("X-example", "hello world");

            // Return the altered response.
            return $response;
        }
    }

    // Create a server
    $server = new Server();

    // Add the header-adding middleware to the server first so that it will
    // forward requests on to the router.
    $server->add(new CustomHeaderMiddleware());

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

.. _middleware: middleware.html
.. _router: router.html
