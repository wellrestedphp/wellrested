Messages and PSR-7
==================

WellRESTed uses PSR-7_ as the interfaces for HTTP messages. This section provides an introduction to working with these interfaces and the implementations provided with WellRESTed. For more information, please read PSR-7_.

Requests
--------

The ``$request`` variable passed to handlers and middleware represents the request message sent by the client. You can inspect this variable to read information such as the request path, method, query, headers, and body.

Let's start with a very simple GET request to the path ``/cats/?color=orange``.

.. code-block:: http

    GET /cats/ HTTP/1.1
    Host: example.com
    Cache-control: no-cache

You can read information from the request in your handler like this:

.. code-block:: php

    class MyHandler implements RequestHandlerInterface
    {
        public function handle(ServerRequestInterface $request): ResponseInterface
        {
            $path = $request->getRequestTarget();
            // "/cats/?color=orange"

            $method = $request->getMethod();
            // "GET"

            $query = $request->getQueryParams();
            /*
                Array
                (
                    [color] => orange
                )
            */
        }
    }

This example shows that you can use:

    - ``getRequestTarget()`` to read the path and query string for the request
    - ``getMethod()`` to read the HTTP verb (e.g., GET, POST, OPTIONS, DELETE)
    - ``getQueryParams()`` to read the query as an associative array

Let's move on to some more interesting features.

Headers
^^^^^^^

The request above also included a ``Cache-control: no-cache`` header. You can read this header a number of ways. The simplest way is with the ``getHeaderLine($name)`` method.

Call ``getHeaderLine($name)`` and pass the case-insensitive name of a header. The method will return the value for the header, or an empty string if the header is not present.

.. code-block:: php

    class MyHandler implements RequestHandlerInterface
    {
        public function handle(ServerRequestInterface $request): ResponseInterface
        {
            // This message contains a "Cache-control: no-cache" header.
            $cacheControl = $request->getHeaderLine("cache-control");
            // "no-cache"

            // This message does not contain any authorization headers.
            $authorization = $request->getHeaderLine("authorization");
            // ""
        }
    }

.. note::

    All methods relating to headers treat header field name case insensitively.


Because HTTP messages may contain multiple headers with the same field name, ``getHeaderLine($name)`` has one other feature: If multiple headers with the same field name are present in the message, ``getHeaderLine($name)`` returns a string containing all of the values for that field, concatenated by commas. This is more common with responses, particularly with the ``Set-cookie`` header, but is still possible for requests.

You may also use ``hasHeader($name)`` to test if a header exists, ``getHeader($name)`` to receive an array of values for this field name, and ``getHeaders()`` to receive an associative array of headers where each key is a field name and each value is an array of field values.


Body
^^^^

PSR-7_ provides access to the body of the request as a stream and—when possible—as a parsed object or array. Let's start by looking at a request with form fields made available as an array.

Parsed Body
~~~~~~~~~~~

When the request contains form fields (i.e., the ``Content-type`` header is either ``application/x-www-form-urlencoded`` or ``multipart/form-data``), the request makes the form fields available via the ``getParsedBody`` method. This provides access to the fields without needing to rely on the ``$_POST`` superglobal.

Given this request:

.. code-block:: http

    POST /cats/ HTTP/1.1
    Host: example.com
    Content-type: application/x-www-form-urlencoded
    Content-length: 23

    name=Molly&color=Calico

We can read the parsed body like this:

.. code-block:: php

    class MyHandler implements RequestHandlerInterface
    {
        public function handle(ServerRequestInterface $request): ResponseInterface
        {
            $cat = $request->getParsedBody();
            /*
                Array
                (
                    [name] => Molly
                    [color] => calico
                )
            */
        }
    }

Body Stream
~~~~~~~~~~~

For other content types, use the ``getBody`` method to get a stream containing the contents of request entity body.

Using a JSON representation of our cat, we can make a request like this:

.. code-block:: http

    POST /cats/ HTTP/1.1
    Host: example.com
    Content-type: application/json
    Content-length: 46

    {
        "name": "Molly",
        "color": "Calico"
    }

We can read and parse the JSON body, and even provide it **as** the parsedBody for later middleware or handler like this:

.. code-block:: php

    class JsonParser implements MiddlewareInterface
    {
        public function process(
            ServerRequestInterface $request, 
            RequestHandlerInterface $handler
        ): ResponseInterface 
        {
            // Parse the body.
            $cat = json_decode((string) $request->getBody());
            /*
                stdClass Object
                (
                    [name] => Molly
                    [color] => calico
                )
            */
            // Add the parsed JSON to the request.
            $request = $request->withParsedBody($cat);
            // Send the request to the next handler.
            return $handler->handle($request);
        }
    }


Because the entity body of a request or response can be very large, PSR-7_ represents bodies as streams using the  ``Psr\Htt\Message\StreamInterface`` (see PSR-7_ Section 1.3).

The JSON example casts the stream to a string, but we can also do things like copy the stream to a local file:

.. code-block:: php

    // Store the body to a temp file.
    $chunkSize = 2048; // Number of bytes to read at once.
    $localPath = tempnam(sys_get_temp_dir(), "body");
    $h = fopen($localPath, "wb");
    $body = $request->getBody();
    while (!$body->eof()) {
        fwrite($h, $body->read($chunkSize));
    }
    fclose($h);

Parameters
^^^^^^^^^^

PSR-7_ eliminates the need to read from many of the superglobals. We already saw how ``getParsedBody`` takes the place of reading directly from ``$_POST`` and ``getQueryParams`` replaces reading from ``$_GET``. Here are some other ``ServerRequestInterface`` methods with **brief** descriptions. Please see PSR-7_ for full details, particularly for ``getUploadedFiles``.

.. list-table::
    :header-rows: 1

    *   - Method
        - Replaces
        - Note
    *   - getServerParams
        - $_SERVER
        - Data related to the request environment
    *   - getCookieParams
        - $_COOKIE
        - Compatible with the structure of $_COOKIE
    *   - getQueryParams
        - $_GET
        - Deserialized query string arguments, if any
    *   - getParsedBody
        - $_POST
        - Request body as an object or array
    *   - getUploadedFiles
        - $_FILES
        - Normalized tree of file upload data

Attributes
^^^^^^^^^^

``ServerRequestInterface`` provides another useful feature called "attributes". Attributes are key-value pairs associated with the request that can be, well, pretty much anything.

The primary use for attributes in WellRESTed is to provide access to path variables when using `template routes`_ or `regex routes`_.

For example, the template route ``/cats/{name}`` matches routes such as ``/cats/Molly`` and ``/cats/Oscar``. When the route is dispatched, the router takes the portion of the actual request path matched by ``{name}`` and provides it as an attribute.

For a request to ``/cats/Rufus``:

.. code-block:: php

    $name = $request->getAttribute("name");
    // "Rufus"

When calling ``getAttribute``, you can optionally provide a default value as the second argument. The value of this argument will be returned if the request has no attribute with that name.

.. code-block:: php

    // Request has no attribute "dog"
    $name = $request->getAttribute("dog", "Bear");
    // "Bear"

Middleware can also use attributes as a way to provide extra information to subsequent handlers. For example, an authorization middleware could obtain an object representing a user and store is as the "user" attribute which later middleware could read.

.. code-block:: php

    class AuthorizationMiddleware implements MiddlewareInterface
    {
        public function process(
            ServerRequestInterface $request, 
            RequestHandlerInterface $handler
        ): ResponseInterface 

            try {
                $user = readUserFromCredentials($request);
            } catch (NoCredentialsSupplied $e) {
                return $response->withStatus(401);
            } catch (UserNotAllowedHere $e) {
                return $response->withStatus(403);
            }

            // Store this as an attribute.
            $request = $request->withAttribute("user", $user);

            // Call the next handler, passing the request with the added attribute.
            // Send the request to the next handler.
            return $handler->handle($request);
        }
    };

    class SecureHandler implements RequestHandlerInterface
    {
        public function handle(ServerRequestInterface $request): ResponseInterface
        {
            // Read the "user" attribute added by a previous middleware.
            $user = $request->getAttribute("user");

            // Do something with $user ...
        }
    }

    $server = new \WellRESTed\Server();
    $server->add(new AuthorizationMiddleware());
    $server->add(new SecureHandler()); // Must be added AFTER authorization to get "user"
    $server->respond();

Responses
---------

PSR-7_ messages are immutable, so you will not be able to alter values of response properties. Instead, ``with*`` methods provide ways to get a copy of the current message with updated properties. For example, ``ResponseInterface::withStatus`` returns a copy of the original response with the status changed.

.. code-block:: php

    // The original response has a 500 status code.
    $response->getStatusCode();
    // 500

    // Replace this instance with a new instance with the status updated.
    $response = $response->withStatus(200);
    $response->getStatusCode();
    // 200

.. note::

    PSR-7_ requests are immutable as well, and we used ``withAttribute`` and ``withParsedBody`` in a few of the examples in the Requests section.

Chain multiple ``with`` methods together fluently:

.. code-block:: php

    // Get a new response with updated status, headers, and body.
    $response = (new Response())
        ->withStatus(200)
        ->withHeader("Content-type", "text/plain")
        ->withBody(new \WellRESTed\Message\Stream("Hello, world!);

Status
^^^^^^

Provide the status code for your response with the ``withStatus`` method. When you pass a standard status code to this method, the WellRESTed response implementation will provide an appropriate reason phrase for you. For a list of reason phrases provided by WellRESTed, see the IANA `HTTP Status Code Registry`_.

.. note::

    The "reason phrase" is the text description of the status that appears in the status line of the response. The "status line" is the very first line in the response that appears before the first header.


Although the PSR-7_ ``ResponseInterface::withStatus`` method accepts the reason phrase as an optional second parameter, you generally shouldn't pass anything unless you are using a non-standard status code. (And you probably shouldn't be using a non-standard status code.)

.. code-block:: php

    // Set the status and view the reason phrase provided.

    $response = $response->withStatus(200);
    $response->getReasonPhrase();
    // "OK"

    $response = $response->withStatus(404);
    $response->getReasonPhrase();
    // "Not Found"

Headers
^^^^^^^

Use the ``withHeader`` method to add a header to a response. ``withHeader`` will add the header if not already set, or replace the value of an existing header with that name.

.. code-block:: php

    // Add a "Content-type" header.
    $response = $response->withHeader("Content-type", "text/plain");
    $response->getHeaderLine("Content-type");
    // text/plain

    // Calling withHeader a second time updates the value.
    $response = $response->withHeader("Content-type", "text/html");
    $response->getHeaderLine("Content-type");
    // text/html

To set multiple values for a given header field name (e.g., for ``Set-cookie`` headers), call ``withAddedHeader``. ``withAddedHeader`` adds the new header without altering existing headers with the same name.

.. code-block:: php

    $response = $response
        ->withHeader("Set-cookie", "cat=Molly; Path=/cats; Expires=Wed, 13 Jan 2021 22:23:01 GMT;")
        ->withAddedHeader("Set-cookie", "dog=Bear; Domain=.foo.com; Path=/; Expires=Wed, 13 Jan 2021 22:23:01 GMT;")
        ->withAddedHeader("Set-cookie", "hamster=Fizzgig; Domain=.foo.com; Path=/; Expires=Wed, 13 Jan 2021 22:23:01 GMT;");

To check if a header exists or to remove a header, use ``hasHeader`` and ``withoutHeader``.

.. code-block:: php

    // Check if a header exists.
    $response->hasHeader("Content-type");
    // true

    // Clone this response without the "Content-type" header.
    $response = $response->withoutHeader("Content-type");

    // Check if a header exists.
    $response->hasHeader("Content-type");
    // false

Body
^^^^

To set the body for the response, pass an instance implementing ``Psr\Http\Message\Stream`` to the ``withBody`` method.

.. code-block:: php

    $stream = new \WellRESTed\Message\Stream("Hello, world!");
    $response = $response->withBody($stream);

WellRESTed provides two ``Psr\Http\Message\Stream`` implementations. You can use these, or any other implementation.

Stream
~~~~~~

``WellRESTed\Message\Stream`` wraps a file pointer resource and is useful for responding with a string or file.

When you pass a string to the constructor, the Stream instance uses `php://temp`_ as the file pointer resource. The string passed to the constructor is automatically stored to ``php://temp``, and you can write more content to it using the ``StreamInterface::write`` method.

.. note::

    ``php://temp`` stores the contents to memory, but switches to a temporary file once the amount of data stored hits a predefined limit (the default is 2 MB).

.. code-block:: php

    // Pass the beginning of the contents to the constructor as a string.
    $body = new \WellRESTed\Message\Stream("Hello ");

    // Append more contents.
    $body->write("world!");

    // Set the body and status code.
    $response = (new Response())
        ->withStatus(200)
        ->withBody($body);

To respond with the contents of an existing file, use ``fopen`` to open the file with read access and pass the pointer to the constructor.

.. code-block:: php

    // Open the file with read access.
    $resource = fopen("/home/user/some/file", "rb");

    // Pass the file pointer resource to the constructor.
    $body = new \WellRESTed\Message\Stream($resource);

    // Set the body and status code.
    $response = (new Response())
        ->withStatus(200)
        ->withBody($body);

NullStream
~~~~~~~~~~

Each PSR-7_ message MUST have a body, so there's no ``withoutBody`` method. You also cannot pass ``null`` to ``withBody``. Instead, use a ``WellRESTed\Messages\NullStream`` to provide a very simple, zero-length, no-content body.

.. code-block:: php

    $response = (new Response())
        ->withStatus(200)
        ->withBody(new \WellRESTed\Message\NullStream());

.. _HTTP Status Code Registry: http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
.. _PSR-7: http://www.php-fig.org/psr/psr-7/
.. _Getting Started: getting-started.html
.. _Middleware: middleware.html
.. _template routes: router.html#template-routes
.. _regex routes: router.html#regex-routes
.. _dependency injection: dependency-injection.html
.. _`php://temp`: http://php.net/manual/ro/wrappers.php.php
