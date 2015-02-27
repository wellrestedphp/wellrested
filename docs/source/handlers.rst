Handlers
========

Handlers serve as bridges between requests and responses. A handler is where you will do things such as retrieve a representation to send as the response or read a representation submitted with a request.

HandlerInterface
^^^^^^^^^^^^^^^^

The HandlerInterface_ is used throughout WellRESTed. It's a very simple and has just one method:

.. code-block:: php

    /**
     * Return the handled response.
     *
     * @param \pjdietz\WellRESTed\RequestInterface $request The request to respond to.
     * @param array|null $args Optional additional arguments.
     * @return \pjdietz\WellRESTed\ResponseInterface The handled response.
     */
    public function getResponse(RequestInterface $request, array $args = null);

To create a handler, define a class that implements this interface. Use the ``getResponse`` method to inspect the request and return an appropriate response.

.. code-block:: php

    use pjdietz\WellRESTed\Interfaces\HandlerInterface;
    use pjdietz\WellRESTed\Interfaces\RequestInterface;
    use pjdietz\WellRESTed\Response;

    class HelloHandler implements HandlerInterface
    {
        public function getResponse(RequestInterface $request, array $args = null)
        {
            $response = new Response();
            $response->setStatusCode(200);
            $response->setHeader("Content-type", "text/plain");
            $response->setBody("Hello, world!");
            return $response;
        }
    }

Request
-------

``getResponse()`` receives two arguments when it is called. The first argument represents the request. The handler can use this to read information such as the HTTP method, headers, query parameters, and entity body.

Here's a handler that reads a representation sent with a ``POST`` request.

.. code-block:: php

    use pjdietz\WellRESTed\Interfaces\HandlerInterface;
    use pjdietz\WellRESTed\Interfaces\RequestInterface;
    use pjdietz\WellRESTed\Response;

    class DogHandler implements HandlerInterface
    {
        public function getResponse(RequestInterface $request, array $args = null)
        {
            $response = new Response();

            // Read the request method.
            $method = $request->getMethod();
            if ($method === "POST") {

                // Read the request body.
                $dog = json_decode($request->getBody());

                // ...Store the representation...

                // Provide the response.
                $response = new Response();
                $response->setStatusCode(201);
                $response->setHeader("Content-type", "application/json");
                $response->setBody(json_encode($newDog);

            } elseif ($method === "OPTIONS") {

                // List the methods are allowed for this endpoint.
                $response->setStatusCode(200);
                $response->setHeader("Allow", "POST,OPTIONS");

            } else {

                // Request did not use one of the allowed verbs.
                $response->setStatusCode(405);
                $response->setHeader("Allow", "POST,OPTIONS");

            }
            return $response;
        }
    }

Array
-----

The second argument is an array of extra data such as variables from a TemplateRoute_ or captures from a RegexRoute_.

Suppose you want an endpoint that will represent one specific cat by ID which the user can read using a ``GET`` request. The endpoint should match requests for paths such as ``/cats/123`` or ``/cats/2``.

Use a TemplateRoute_ to extract the ID from the path.

.. code-block:: php

    // Use a TemplateRoute to extract the ID from the path.
    $router->add("/cats/{id}", "\\MyApi\\CatItemHandler");

The extracted ID will be made available to the handler as an array element in the second argument sent to ``getResponse``.

.. code-block:: php

    namesapce MyApi;

    Class CatItemHandler implements HandlerInterface
    {
        public function getResponse(RequestInterface $request, array $args = null)
        {
            $response = new Response();

            // Determine how to respond based on the request's HTTP method.
            $method = $rqst->getMethod();
            if ($method === "GET") {
                // Lookup the cat using the ID from the path.
                $cat = $this->getCatById($args["id"]));
                if ($cat) {
                    // Respond with a representation.
                    $response->setStatusCode(200);
                    $response->setHeader("Content-type", "application/json");
                    $response->setBody(json_encode($cat));
                } else {
                    // Or, a Not Found error if there's no cat with that ID.
                    $response->setStatusCode(404);
                }
            } elseif ($method === "OPTIONS") {
                // User wants to know what verbs are allowed for this endpoint.
                $response->setStatusCode(200);
                $response->setHeader("Allow", "GET,HEAD,OPTIONS");
            } else {
                // User did not use one of the allowed verbs.
                $response->setStatusCode(405);
                $response->setHeader("Allow", "GET,HEAD,OPTIONS");
            }
            return $response;
        }

        private getCatById($id)
        {
            // ... Lookup the cat from storage and return it.
        }
    }

Handler Class
^^^^^^^^^^^^^

When you write your handlers, you can either write them from scratch and implement ``HandlerInterface`` as we did above, or you can extend the abstract ``Handler`` class which provides some convenient features such as instance members for the request and response and methods for the most common HTTP verbs.

Instance Members
----------------

============  ====================  ===================================================================
Member	      Type	                Description
============  ====================  ===================================================================
``args``      ``array``             Hash to supplement the request; usually path variables.
``request``   ``RequestInterface``  The HTTP request to respond to.
``response``  ``Response``          The HTTP response to send based on the request.
============  ====================  ===================================================================

HTTP Verbs
----------

Most of the action takes place inside the methods called in response to specific HTTP verbs. For example, to handle a ``GET`` request, implement the ``get`` method.

.. code-block:: php

    class CatsCollectionHandler extends \pjdietz\WellRESTed\Handler
    {
        protected function get()
        {
            // Read some cats from storage.
            // ...read these an array as the variable $cats.

            // Set the values for the instance's response member. This is what the
            // Router will eventually output to the client.
            $this->response->setStatusCode(200);
            $this->response->setHeader("Content-Type", "application/json");
            $this->response->setBody(json_encode($cats));
        }
    }

Implement the methods that you want to support. If you don't want to support ``POST``, don't implement it. The default behavior is to respond with ``405 Method Not Allowed`` for most verbs.

The methods available to implement are:

===========  ===========  ===========================================
HTTP Verb    Method       Default Behavior
===========  ===========  ===========================================
``GET``      ``get``      405 Method Not Allowed
``HEAD``     ``head``     Call ``get``, then clean the response body
``POST``     ``post``     405 Method Not Allowed
``PUT``      ``put``      405 Method Not Allowed
``DELETE``   ``delete``   405 Method Not Allowed
``PATCH``    ``patch``    405 Method Not Allowed
``OPTIONS``  ``options``  Add ``Allow`` header, if able
===========  ===========  ===========================================

OPTIONS and Allow
-----------------

If you wish to provide a list of verbs that the endpoint supports, you can do this by redefining ``getAllowedMethods`` and returning an array of verbs. The handler will use this list to provide an ``Allow`` header when responding to ``OPTIONS`` requests or to requests for verbs the handler does not allow.

.. code-block:: php

    protected function getAllowedMethods()
    {
        return ["GET", "HEAD", "POST", "OPTIONS"];
    }

An ``OPTIONS`` request handled by this handler will now get a response similar to this:

.. code-block:: http

    HTTP/1.1 200 OK
    Allow: GET, HEAD, POST, OPTIONS

A ``POST`` request's response will look like this:

.. code-block:: http

    HTTP/1.1 405 Method Not Allowed
    Allow: GET, HEAD, POST, OPTIONS

.. _Dependency Injection: dependency-injection.html
.. _TemplateRoute: routes.html#template-routes
.. _RegexRoute: routes.html#regex-routes
.. _Routers: router.html
