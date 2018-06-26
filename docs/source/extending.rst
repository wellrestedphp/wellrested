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

The dispatcher is an instance that unpacks your handlers and middleware and sends the request and response through it. A default dispatcher is created for you when you use your ``WellRESTed\Server``.

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

To use this dispatcher, create an instance implementing ``WellRESTed\Dispatching\DispatcherInterface`` and pass it to the server's ``setDispatcher`` method.

.. code-block:: php

    $server = new WellRESTed\Server();
    $server->setDispatcher(new MyApi\CustomDispatcher());

Message Customization
---------------------

In the example above, we passed a custom dispatcher to the server. You can also customize your server in other ways. For example, when the server reaches these end of its stack of middleware and has not had the response handled, it returns a blank 404 error response. You can customize this by passing a response to the server's ``setUnhandledResponse`` method.

.. code-block:: php

    $unhandled = (new Response(404))
        ->withHeader('text/html')
        ->withBody($fancy404message);
    
    $server->setUnhandledResponse($unhandled);

.. _PSR-7: https://www.php-fig.org/psr/psr-7/
.. _Handlers and Middleware: handlers-and-middleware.html
.. _Request Attributes: messages.html#attributes
