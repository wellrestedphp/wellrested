Getting Started
===============

This page provides a brief introduction to WellRESTed. We'll start with a `Hello, world!`_, take a quick peek at `using handlers`_, and finally explore reacting to `HTTP methods`_.

Hello, World!
^^^^^^^^^^^^^

Let's start with a very basic "Hello, world!". Here, we will create a router_ that will look for requests to ``/hello`` and respond with "Hello, world!"

.. code-block:: php

    <?php

    use pjdietz\WellRESTed\Response;
    use pjdietz\WellRESTed\Router;

    require_once "vendor/autoload.php";

    // Create a router.
    $router = new Router();

    // Add a route that matches the path "/hello" and returns a response.
    $router->add("/hello", function () {
        $response = new Response();
        $response->setStatusCode(200);
        $response->setHeader("Content-type", "text/plain");
        $response->setBody("Hello, world!");
        return $response;
    });

    // Read the request sent to the server and use it to output a response.
    $router->respond();

In this example, we created a router, then added one "route" to it. This route matches requests with the path ``/hello`` and dispatches them to a callable. The callable builds and returns a response.

Reading from the Request
------------------------

This is a good start, but it's not very dynamic. Rather than always responding with the same message, let's respond with a greeting using some information from the request's query.

.. code-block:: php

    <?php

    use pjdietz\WellRESTed\Interfaces\RequestInterface;
    use pjdietz\WellRESTed\Response;
    use pjdietz\WellRESTed\Router;

    require_once "vendor/autoload.php";

    $router = new Router();

    // Add a route that matches the path "/hello" and returns a response.
    $router->add("/hello", function (RequestInterface $request) {

        // Provide a default.
        $name = "world";

        // Read from the query.
        $query = $request->getQuery();
        if (isset($query["name"])) {
            $name = $query["name"];
        }

        $response = new Response();
        $response->setStatusCode(200);
        $response->setHeader("Content-type", "text/plain");
        $response->setBody("Hello, $name!");
        return $response;
    });

    $router->respond();

With this router, requests for ``/hello?name=Molly`` will get the response "Hello, Molly" while requests for ``/hello?name=Oscar`` will get "Hello, Oscar!"

The callable we passed to ``$router->add()`` receives a variable called ``$request`` that represents the request being dispatched. The ``RequestInterface`` provides access to information such as the request method (e.g., "GET", "POST", "PUT", "DELETE"), headers, the entity body, and the query.

Reading from the Path
---------------------

Instead of specifying the name for the greeting in the query, let's modify our API to look for the name in the path so that a request to ``/hello/Molly`` will provide a "Hello, Molly!" response.

.. code-block:: php

    <?php

    use pjdietz\WellRESTed\Interfaces\RequestInterface;
    use pjdietz\WellRESTed\Response;
    use pjdietz\WellRESTed\Router;

    require_once "vendor/autoload.php";

    $router = new Router();

    // Add a route that matches the path "/hello/" followed by a name.
    // The route's handler will return a personalized greeting.
    $router->add("/hello/{name}", function (RequestInterface $request, array $args) {

        // The part of the path where {name} appear will be extracted
        // and provided to the callable as the "name" array element.
        $name = $args["name"];

        $response = new Response();
        $response->setStatusCode(200);
        $response->setHeader("Content-type", "text/plain");
        $response->setBody("Hello, $name!");
        return $response;
    });

    $router->respond();

Notice that the first parameter passed to ``$router->add()`` is now "/hello/{name}". The curly braces define a variable that will match text in that section of the path and provide it to the callable by adding an array element. Since "name" appears inside the braces, "name" will be the key; the matched text ("Molly" for requests to ``/hello/Molly``) will be the value.

To learn more about extracting variables from the path, see TemplateRoutes_ and RegexRoutes_.

Using Handlers
^^^^^^^^^^^^^^

So far, the examples have been limited to building the entire Web service inside a single ``index.php`` file. For an actual site, you'll want to spread your code across many files.

Let's start by replacing the callable we used above with a handler. In WellRESTed, a "handler" is a piece of middleware that takes a request and provides a response. We used callables as informal handlers above, but the more formal approach is to use HandlerInterface_.

.. code-block:: php

    <?php

    namespace MyApi;

    use pjdietz\WellRESTed\Interfaces\HandlerInterface;
    use pjdietz\WellRESTed\Interfaces\RequestInterface;
    use pjdietz\WellRESTed\Response;

    class HelloHandler implements HandlerInterface
    {
        public function getResponse(RequestInterface $request, array $args = null)
        {
            $name = $args["name"];

            $response = new Response();
            $response->setStatusCode(200);
            $response->setHeader("Content-type", "text/plain");
            $response->setBody("Hello, $name!");
            return $response;
        }
    }

When we add the route to the router, we can specify the handler by providing a string containing the handler's fully qualified class name (FQCN). When the router receives a request that matches the ``/hello/{name}`` path, it will instantiate ``MyApi\HelloHandler`` and use the instance to get a response.

Here's the updated ``index.php``

.. code-block:: php

    <?php

    use pjdietz\WellRESTed\Router;

    require_once "vendor/autoload.php";

    $router = new Router();
    $router->add("/hello/{name}", "\\MyApi\\HelloHandler");
    $router->respond();

HTTP Methods
^^^^^^^^^^^^

The examples so far have not touched on HTTP methods—the "Hello, world!" handlers give the same responses for GET, POST, PUT, etc.

Let's set aside "Hello, world!" for now, and imagine tiny RESTful API about cats. We'll start with a ``/cats/`` endpoint that should allow these requests:

``GET /cats/``
    Output a list of cat representations in JSON.

``POST /cats/``
    Accept a JSON representation of a cat and store it.

``OPTIONS /cats/``
    List the methods the endpoint allows.

(We won't actually store any cats here. The example is just to show how the HTTP parts work.)

HandlerInterface
----------------

We can react to the verbs using a class implementing HandlerInterface_ like this:

.. code-block:: php

    namespace MyApi;

    use pjdietz\WellRESTed\Interfaces\HandlerInterface;
    use pjdietz\WellRESTed\Interfaces\RequestInterface;
    use pjdietz\WellRESTed\Interfaces\ResponseInterface;
    use pjdietz\WellRESTed\Response;

    class CatHandler implements HandlerInterface
    {
        public function getResponse(RequestInterface $request, array $args = null)
        {
            $response = new Response();

            // Determine how to respond based on the request's HTTP method.
            $method = $request->getMethod();
            if ($method === "GET") {
                // Read the list of cats.
                $cats = $this->getCats();
                $response->setStatusCode(200);
                $response->setHeader("Content-type", "application/json");
                $response->setBody(json_encode($cats));
            } elseif ($method === "POST") {
                // Read the cat from the request body.
                $cat = json_decode($request->getBody());
                // Store it, and read the updated representation.
                $newCat = $this->storeCat($cat);
                $response->setStatusCode(201);
                $response->setHeader("Content-type", "application/json");
                $response->setBody(json_encode($newCat));
            } elseif ($method === "OPTIONS") {
                // List the methods are allowed for this endpoint.
                $response->setStatusCode(200);
                $response->setHeader("Allow", "GET,HEAD,POST,OPTIONS");
            } else {
                // Request did not use one of the allowed verbs.
                $response->setStatusCode(405);
                $response->setHeader("Allow", "GET,HEAD,POST,OPTIONS");
            }
            return $response;
        }

        private function getCats()
        {
            // ...Read cats from storage...
        }

        private function storeCat($cat)
        {
            // ...Store cats here...
        }
    }

Handler Class
-------------

Not bad, but that's a lot of branching. We can clean this up a bit by deriving our ``CatHandler`` from `pjdietz\\WellRESTed\\Handler`__, an abstract class that implements ``HandlerInterface`` and provides protected methods for you to override that correspond with HTTP methods. Here's ``CatHandler`` refactored as a Handler_ subclass.

__ Handler_

.. code-block:: php

    <?php

    namespace MyApi;

    use pjdietz\WellRESTed\Handler;

    class CatHandler extends Handler
    {
        protected function get()
        {
            // Read the list of cats.
            $cats = $this->getCats();
            $this->response->setStatusCode(200);
            $this->response->setHeader("Content-type", "application/json");
            $this->response->setBody(json_encode($cats));
        }

        protected function post()
        {
            // Read the cat from the request body.
            $cat = json_decode($this->request->getBody());
            // Store it, and read the updated representation.
            $newCat = $this->storeCat($cat);
            $this->response->setStatusCode(201);
            $this->response->setHeader("Content-type", "application/json");
            $this->response->setBody(json_encode($newCat));
        }

        protected function getAllowedMethods()
        {
            return ["GET","HEAD","POST","OPTIONS"];
        }

        private function getCats()
        {
            // ...Read cats from storage...
        }

        private function storeCat($cat)
        {
            // ...Store cats here...
        }
    }

Using Handler_, we override the methods for verbs we want to support; all other will automatically respond ``405 Method Not Allowed``. In addition, returning an array of verbs from ``getAllowedMethods`` adds an ``Allow`` header for ``405 Method Not Allowed`` responses, and automatically provides for ``OPTIONS`` support.

Note that when using Handler_, you interact with the request and response through the protected instance members ``$this->request`` and ``$this->response``. The array provided to ``HandlerInterface:getResponse`` as the second argument is available as ``$this->args``.

.. _TemplateRoutes: routes.html#template-routes
.. _RegexRoutes: routes.html#regex-routes
.. _router: router.html
.. _HandlerInterface: handlers.html#handlerinterface
.. _Handler: handlers.html#handler-class
