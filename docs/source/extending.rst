Extending and Customizing
=========================

WellRESTed is designed with customization in mind. This section describes some common scenarios for customization, starting with using a handler that implements a different interface.

Custom Handlers and Middleware
------------------------------

Imagine you found a handler class from a third party that does exactly what you need. The only problem is that it implements a different interface.

Here's the interface for the third-party handler:

.. code-block:: php

    interface OtherHandlerInterface
    {
        /**
         * @param ServerRequestInterface $request
         * @return ResponseInterface
         */
        public function run(ResponseInterface $response);
    }

Wrapping
^^^^^^^^

One solution is to wrap an instance of this handler inside of a ``Psr\Http\Server\RequestHandlerInterface`` instance.

.. code-block:: php

    /**
     * Wraps an instance of OtherHandlerInterface
     */
    class OtherHandlerWrapper implements RequestHandlerInterface
    {
        private $handler;

        public function __construct(OtherHandlerInterface $handler)
        {
            $this->handler = $handler;
        }

        public function handle(ServerRequestInterface $request): ResponseInterface
        {
            return $this->handler->run($request);
        }
    }

Custom Dispatcher
^^^^^^^^^^^^^^^^^

Wrapping works well when you have one or two handlers implementing a third-party interface. If you want to integrate a lot of classes that implement a given third-party interface, you're might consider customizing the dispatcher.

The dispatcher is an instance that unpacks your handlers and middleware and sends the request and response through it. A default dispatcher is created for you when you instantiate your ``WellRESTed\Server`` (without passing the second argument). The server instantiates a ``WellRESTed\Dispatching\Dispatcher`` which is capable of running handlers and middleware as described in the `Handlers and Middleware`_. 

If you need the ability to dispatch other types of middleware, you can create your own by implementing ``WellRESTed\Dispatching\DispatcherInterface``. The easiest way to do this is to subclass ``WellRESTed\Dispatching\Dispatcher``. Here's an example that extends ``Dispatcher`` and adds support for ``OtherHandlerInterface``:

.. code-block:: php

    /**
     * Dispatcher with support for OtherHandlerInterface
     */
    class CustomDispatcher extends \WellRESTed\Dispatching\Dispatcher
    {
        public function dispatch(
            $dispatchable,
            ServerRequestInterface $request,
            ResponseInterface $response,
            $next
        ) {
            try {
                // Use the dispatch method in the parent class first.
                $response = parent::dispatch($dispatchable, $request, $response, $next);
            } catch (\WellRESTed\Dispatching\DispatchException $e) {
                // If there's a problem, check if the handler or middleware
                // (the "dispatchable") implements OtherHandlerInterface. 
                // Dispatch it if it does.
                if ($dispatchable instanceof OtherHandlerInterface) {
                    $response = $dispatchable->run($request);
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

    // Now, you can add any handlers implementing OtherHandlerInterface
    $other = new OtherHandler();
    $server->add($other);

Message Customization
---------------------

In the example above, we passed a custom dispatcher to the server. You can also customize your server in other ways. For example, if you have a different implementation of PSR-7_ messages that you prefer, you can pass them into the ``Server::respond`` method:

.. code-block:: php

    // Represents the request submitted by the client.
    $request = new ThirdParty\Request();
    // A "blank" response.
    $response = new ThirdParty\Response();

    $server = new WellRESTed\Server();
    // ...add middleware and handlers...

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

In addition to the messages, you can do similar customization for other ``Server`` dependencies such as the dispatcher (see above), the transmitter (which writes the response out to the client), and the routers that are created with ``Server::createRouter``. These dependencies are instantiated in isolated methods as with the request and response to make this sort of customization easy, and other classes such as ``Router`` use this pattern as well.

.. _PSR-7: https://www.php-fig.org/psr/psr-7/
.. _Handlers and Middleware: handlers-and-middleware.html
.. _Request Attributes: messages.html#attributes
