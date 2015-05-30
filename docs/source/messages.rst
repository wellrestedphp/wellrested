Messages and PSR-7
==================

WellRESTed uses PSR-7_ as the interfaces for HTTP messages. This section provides an introduction to working with these interfaces and the implementations provided with WellRESTed. For more information, please read PSR-7_.

Obtaining Instances
-------------------

When working with middleware_, you generally will not need to create requests and responses yourself, as these are passed into the middleware when it is dispatched.

In `Getting Started`_, we saw that middleware looks like this:

.. code-block:: php

    /**
     * @param Psr\Http\Message\ServerRequestInterface $request
     * @param Psr\Http\Message\ResponseInterface $response
     * @param callable $next
     * @return Psr\Http\Message\ResponseInterface
     */
    function ($request, $response, $next) { }

When middleware is called, it receives a ``Psr\Http\Message\ServerRequestInterface`` instance representing the client's request and a ``Psr\Http\Message\ResponseInterface`` instance that serves as a starting place for the response to output to the client. These instances are created by the ``WellRESTed\Server`` when you call ``WellRESTed\Server::respond``.

.. note::

    If you want to provide your own custom request and response (either to adjust the initial settings or to use a different implementation), you can do so by passing request and response instances as the first and second parameters to ``WellRESTed\Server::respond``.

Requests
--------

The ``$request`` variable passed to middleware represents the request message sent by the client. Middleware can inspect this variable to read information such as the request path, method, query, headers, and body.

Let's start with a very simple GET request to the path ``/cats/?color=orange``.

.. code-block:: http

    GET /cats/ HTTP/1.1
    Host: example.com
    Cache-control: no-cache

You can read information from the request in your middleware like this:

.. code-block:: php

    function ($request, $response, $next) {

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

This example middleware shows that you can use:

    - ``getRequestTarget()`` to read the path and query string for the request
    - ``getMethod()`` to read the HTTP verb (e.g., GET, POST, OPTIONS, DELETE)
    - ``getQueryParams()`` to read the query as an associative array

Let's move on to some more intersting features.

Headers
^^^^^^^

The request above also included a ``Cache-control: no-cache`` header. You can read this header a number of ways. The simplest way is with the ``getHeaderLine($name)`` method.

Call ``getHeaderLine($name)`` and pass the case-insensitive name of a header. The method will return the value for the header, or an empty string.

.. code-block:: php

    function ($request, $response, $next) {

        // This message contains a "Cache-control: no-cache" header.
        $cacheControl = $request->getHeaderLine("cache-control");
        // "no-cache"

        // This message does not contain any authoriation headers.
        $authoriziation = $request->getHeaderLine("authorization");
        // ""

    }

.. note::

    All methods relating to headers treat header field name case insensitively.


Because HTTP messages may contain multiple headers with the same field name, ``getHeaderLine($name)`` has one other feature: If multiple headers with the same field name are present in the message, ``getHeaderLine($name)`` returns a string containing all of the values for that field, concatenated by commas. This is more common with responses, paricularaly with the ``Set-cookie`` header, but is still possible for requests.

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

    function ($request, $response, $next) {

        $cat = $request->getParsedBody();
        /*
            Array
            (
                [name] => Molly
                [color] => calico
            )
        */

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

We can read and parse the JSON body, and even provide it **as** the parsedBody for later middleware like this:

.. code-block:: php

    function ($request, $response, $next) {

        $cat = json_decode((string) $request->getBody());
        /*
            stdClass Object
            (
                [name] => Molly
                [color] => calico
            )
        */

        $request = $request->withParsedBody($cat);

    }


Because the entity body of a request or response can be very large, PSR-7_ represents bodies as streams using the  ``Psr\Htt\Message\StreamInterface`` (see PSR-7_ Section 1.3).

The JSON example cast the stream to a string, but we can also do things like copy the stream to a local file:

.. code-block:: php

    function ($request, $response, $next) {

        // Store the body to a temp file.
        $chunkSize = 2048; // Number of bytes to read at once.
        $localPath = tempnam(sys_get_temp_dir(), "body");
        $h = fopen($localPath, "wb");
        $body = $rqst->getBody();
        while (!$body->eof()) {
            fwrite($h, $body->read($chunkSize));
        }
        fclose($h);

    }

Paramters
^^^^^^^^^

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

    function ($request, $response, $next) {

        $name = $request->getAttribute("name");
        // "Rufus"

    }

When calling ``getAttribute``, you can optionally provide a default value as the second argument. The value of this argument will be returned if the request has no attribute with that name.

.. code-block:: php

    function ($request, $response, $next) {

        // Request has no attribute "dog"
        $name = $request->getAttribute("dog", "Bear");
        // "Bear"

    }

Middleware can also use attributes as a way to provide extra information to subsequent middleware. For example, an authorization middleware could obtain an object representing a user and store is as the "user" attribute which later middleware could read.

.. code-block:: php

    $auth = function ($request, $response, $next) {

        try {
            $user = readUserFromCredentials($request);
        } catch (NoCredentialsSupplied $e) {
            return $response->withStatus(401);
        } catch (UserNotAllowedHere $e) {
            return $response->withStatus(403);
        }

        // Store this as an attribute.
        $request = $request->withAttribute("user", $user);

        // Call $next, passing the request with the added attribute.
        return $next($request, $response);

    };

    $subsequent = function ($request, $response, $next) {

        // Read the "user" attribute added by a previous middleware.
        $user = $request->getAttribute("user");

        // Do something with $user

    }

    $server = new \WellRESTed\Server();
    $server->add($auth);
    $server->add($subsequent); // Must be added AFTER $auth to get "user"
    $server->respond();

Finally, attributes provide a nice way to provide a `dependency injection`_ container for to your middleware.

Requests
--------

Coming soon!

.. _PSR-7: http://www.php-fig.org/psr/psr-7/
.. _Getting Started: getting-started.html
.. _Middleware: middleware.html
.. _template routes: router.html#template-routes
.. _regex routes: router.html#regex-routes
.. _dependency injection: dependency-injection.html
