Extending and Customizing
=========================

WellRESTed is designed with customization in mind. This section will describe some common scenarios for customization, starting with using middleware that implements a different interface.

Custom Middleware
-----------------

Imagine you found a middleware class from a third party that does exactly what you need. The only problem is that it implements a different middleware interface.

Here's the interface for the third-party middleware:

.. code-block:: php

    interface OtherMiddlewareInterface
    {
        /**
         * @param \Psr\Http\Message\ServerRequestInterface $request
         * @param \Psr\Http\Message\ResponseInterface $response
         * @return \Psr\Http\Message\ResponseInterface
         */
        public function run(
            \Psr\Http\Message\ServerRequestInterface $request,
            \Psr\Http\Message\ResponseInterface $response
        );
    }

Wrapping
^^^^^^^^

One solution is to wrap an instance of this middleware inside of a ``WellRESTed\MiddlewareInterface`` instance.

.. code-block:: php

    /**
     * Wraps an instance of OtherMiddlewareInterface
     */
    class OtherWrapper implements \WellRESTed\MiddlewareInterface
    {
        private $middleware;

        public function __construct(OtherMiddlewareInterface $middleware)
        {
            $this->middleware = $middleware;
        }

        public function __invoke(
            \Psr\Http\Message\ServerRequestInterface $request,
            \Psr\Http\Message\ResponseInterface $response,
            $next
        ) {
            // Run the wrapped middleware.
            $response = $this->middleware->run($request, $response);
            // Pass the middleware's response to $next and return the result.
            return $next($request, $myResponse);
        }
    }

.. note::

    ``OtherMiddlewareInterface`` doesn't provide any information about how to propagate the request and response through a chain of middleware, so I chose to call ``$next`` every time. If there's a sensible way to tell that you should stop propagating, your wrapper class could return a response without calling ``$next`` under those circumstances. It's up to you and the middleware you're wrapping.


To use this wrapped middleware, you can do something like this:

.. code-block:: php

    // The class we need to wrap; implements OtherMiddlewareInterface
    $other = new OtherMiddleware();

    // The wrapper class; implements WellRESTed\MiddlewareInterface
    $otherWrapper = new OtherWrapper($other)

    $server = new WellRESTed\Server();
    $server->add($otherWrapper);

Custom Dispatcher
^^^^^^^^^^^^^^^^^

Wrapping works well when you have one or two middleware implementing a third-party interface. If you want to integrate a lot of middleware classes that implement a given third-party interface, you're better off customizing the dispatcher.

The dispatcher is an instance that unpacks your middleware and sends the request and response through it. A default dispatcher is created for you when you instantiate your ``WellRESTed\Server`` (without passing the second argument). The server instantiates a ``WellRESTed\Dispatching\Dispatcher`` which is capable of running middleware provided as a callable, a string containing the fully qualified class name of a middleware, or an array of middleware. (See `Using Middleware`_ for a description of what a default dispatcher can dispatch.)

If you need the ability to dispatch other types of middleware, you can create your own by implementing ``WellRESTed\Dispatching\DispatcherInterface``. The easiest way to do this is to subclass ``WellRESTed\Dispatching\Dispatcher``. Here's an example that extends ``Dispatcher`` and adds support for ``OtherMiddlewareInterface``:

.. code-block:: php

    namespace MyApi;

    /**
     * Dispatcher with support for OtherMiddlewareInterface
     */
    class CustomDispatcher extends \WellRESTed\Dispatching\Dispatcher
    {
        public function dispatch(
            $middleware,
            \Psr\Http\Message\ServerRequestInterface $request,
            \Psr\Http\Message\ResponseInterface $response,
            $next
        ) {
            try {
                // Use the dispatch method in the parent class first.
                $response = parent::dispatch($middleware, $request, $response, $next);
            } catch (\WellRESTed\Dispatching\DispatchException $e) {
                // If there's a problem, check if the middleware implements
                // OtherMiddlewareInterface. Dispatch it if it does.
                if ($middleware instanceof OtherMiddlewareInterface) {
                    $response = $middleware->run($request, $response);
                    $response = $next($request, $response);
                } else {
                    // Otherwise, re-throw the exception.
                    throw $e;
                }
            }
            return $response;
        }
    }

To use this dispatcher, pass it to the constructor of ``WellRESTed\Server`` as the second argument. (The first argument is a hash array to use as `request attributes`_.)

.. code-block:: php

    // Create an instance of your custom dispatcher.
    $dispatcher = new MyApi\CustomDispatcher;

    // Pass this dispatcher to the server.
    $server = new WellRESTed\Server(null, $dispatcher);

    // Now, you can add any middleware implementing OtherMiddlewareInterface
    $other = new OtherMiddleware();
    $server->add($other);

    // Registering OtherMiddlewareInterface middleware by FQCN will work, too.

Message Customization
---------------------

In the example above, we passed a custom dispatcher to the server. You can also customize your server in other ways. For example, if you have a different implementation of PSR-7_ messages that you prefer, you can pass them into the ``Server::respond`` method:

.. code-block:: php

    // Represents the request submitted by the client.
    $request = new ThirdParty\Request();
    // A "blank" response.
    $response = new ThirdParty\Response();

    $server = new WellRESTed\Server();
    // ...add middleware...

    // Pass your request and response to Server::respond
    $server->response($request, $response);

Even if you don't want to use a different implementation, you may still find a reason to provide you're own messages. For example, the default response status code for a ``WellRESTed\Message\Response`` is 500. If you wanted to make the default 200 instead, you could do something like this:

.. code-block:: php

    // The first argument is the status code.
    $response = new \WellRESTed\Message\Response(200);

    $server = new \WellRESTed\Server();
    // ...add middleware...

    // Pass the response to respond()
    $server->respond(null, $response);

Server Customization
--------------------

As an alternative to passing you preferred request and response instances into ``Server::respond``, you can extend ``Server`` to obtain default values from a different source.

Classes such as ``Server`` that create dependencies as defaults keep the instantiation isolated in easy-to-override methods. For example, ``Server`` has a protected method ``getResponse`` that instantiates and returns a new response. You can easily replace this method with your own that returns the default response of your choice.

For example, imagine you have a dependency container that provides the starting messages for you. You can subclass ``Server`` to obtain and use these messages as defaults like this:

.. code-block:: php

    class CustomerServer extends WellRESTed\Server
    {
        /** @var A dependency container */
        private $container;

        public function __construct(
            $container,
            array $attributes = null,
            DispatcherInterface $dispatcher = null,
            $pathVariablesAttributeName = null
        ) {
            // Call the parent constructor with the expected parameters.
            parent::__construct($attributes, $dispatcher, $pathVariablesAttributeName);
            // Store the container.
            $this->container = $container;
        }

        /**
         * Redefine this method, which is called in Server::respond when
         * the caller does not provide a request.
         */
        protected function getRequest()
        {
            // Return a request obtained from the container.
            return $this->container["request"];
        }

        /**
         * Redefine this method, which is called in Server::respond when
         * the caller does not provide a response.
         */
        protected function getResponse()
        {
            // Return a response obtained from the container.
            return $this->container["response"];
        }
    }

In addition to the messages, you can do similar customization for other ``Server`` dependencies such as the dispatcher (see above), the transmitter (which writes the response out to the client), and the routers that are created with ``Server::createRouter``. These dependencies are instantiated in isolated methods as with the request and response to make this sort of customization easy, and other classes such as ``Router`` use this pattern as well. See the source code, and don't hesitated to subclass.

.. _PSR-7: http://www.php-fig.org/psr/psr-7/
.. _Using Middleware: middleware.html#using-middleware
.. _Request Attributes: messages.html#attributes
