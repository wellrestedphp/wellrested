Router
======

A router organizes the components of a site by associating URI paths with handlers_. When the router receives a request, it examines the request's URI, determines which "route" matches, and dispatches the associated handler_. The handler_ is then responsible for reacting to the request and providing a response.

A typical WellRESTed Web service will have a single point of entry (usually ``/index.php``) that the Web server directs all traffic to. This script instantiates a ``Router``, populates it with routes_, and dispatches the request. Here's an example:

.. code-block:: php

    <?php

    use pjdietz\WellRESTed\Response;
    use pjdietz\WellRESTed\Router;

    require_once "vendor/autoload.php";

    // Create a new router.
    $router = new Router();

    // Populate the router with routes.
    $router->add(
        ["/", "\\MyApi\\RootHandler"],
        ["/cats/", "\\MyApi\\CatHandler"],
        ["/dogs/*", "\\MyApi\\DogHandler"],
        ["/guinea-pigs/{id}", "\\MyApi\\GuineaPigHandler"],
        ["~/hamsters/([0-9]+)~", "\\MyApi\\HamsterHandler"]
    );

    // Output a response based on the request sent to the server.
    $router->respond();

Adding Routes
^^^^^^^^^^^^^

Use the ``Router::add`` method to associate a URI path with a handler_.

Here we are specifying that requests for the root path ``/`` should be handled by the class with the name ``MyApi\RootHandler``.

.. code-block:: php

    $router->add("/", "\\MyApi\\RootHandler");

You can add routes individually, or you can add multiple routes at once. When adding multiple routes, pass a series of arrays to ``Router::add`` where each array's first item is the path and the second is the handler_.

.. code-block:: php

    $router->add(
        ["/", "\\MyApi\\RootHandler"],
        ["/cats/", "\\MyApi\\CatHandler"],
        ["/dogs/*", "\\MyApi\\DogHandler"],
        ["/guinea-pigs/{id}", "\\MyApi\\GuineaPigHandler"],
        ["~/hamsters/([0-9]+)~", "\\MyApi\\HamsterHandler"]
    );

.. note::

    WellRESTed provides several types of routes including routes that match paths by regular expressions and routes that match by URI templates. See Routes_ to learn more about the different types of routes available.

Specifying Handlers
^^^^^^^^^^^^^^^^^^^

When the router finds a route that matches the request, it dispatches the associated handler_ (typically a class that implements HandlerInterface_). When adding routes (or `error handlers`_), you can specify the handler_ to dispatch in a number of ways.

.. _error handlers: `Error Handling`_

Fully Qualified Class Name (FQCN)
---------------------------------

Specify a class by FQCN by passing a string as the second parameter.

.. code-block:: php

    $router->add("/cats/{id}", "\\MyApi\\CatHandler");

Handlers_ specified by FQCN are not instantiated (or even autoloaded) immediately. The router waits until it identifies that a request should be dispatched to a handler specified by FQCN. Then, it creates an instance of the specified class. Finally, the router calls the handler instance's ``getResponse`` method (declared in HandlerInterface_) and outputs the returned response.

Because the instantiation and autoloading are delayed, a router with 100 routes_ will still only autoload and instantiate one handler_ class throughout any individual request-response cycle.


Callable
--------

You can also use a callable to instantiate and return the handler_.

.. code-block:: php

    $router->add("/cats/{id}", function () {
        return new \MyApi\CatItemHandler();
    });

This still delays instantiation, but gives you some added flexibility. For example, you could define a handler_ class that receives some configuration upon construction.

.. code-block:: php

    $container = new MySuperCoolDependencyContainer();

    $router->add("/cats/{id}", function () use ($container) {
        return new \MyApi\CatItemHandler($container);
    });

This is one approach to `dependency injection`_.

You can also return a response directly from a callable. The callables actually serve as informal handlers_ and receive the same arguments as ``HandlerInterface::getResponse``.

.. code-block:: php

    $router->add("/hello/{name}", function ($rqst, $args) {
        $name = $args["name"];
        $response = new \pjdietz\WellRESTed\Response();
        $response->setStatusCode(200);
        $response->setBody("Hello, $name!");
        return $response;
    });

Instance
--------

The simplest way to use a handler_ is to instantiate it yourself and pass the instance.

.. code-block:: php

    $router->add("/cats/{id}", new \MyApi\CatItemHandler());

This is easy, but has a significant disadvantage over the other options because each handler_ used this way will be autoloaded and instantiated, even though only one handler_ will actually be used for a given request-response cycle. You may find this approach useful for testing, but avoid if for production code.

Error Handling
^^^^^^^^^^^^^^

Use ``Router::setErrorHandler`` to provide responses for a specific status codes. The first argument is the integer status code; the second is a handler_, provided in one of the forms listed in the `Specifying Handlers`_ section.

.. code-block:: php

    $router->setErrorHandler(400, "\\MyApi\\BadRequestHandler");
    $router->setErrorHandler(401, function () {
        return new \MyApi\UnauthorizedHandler();
    });
    $router->setErrorHandler(403, function () {
        $response = new \pjdietz\WellRESTed\Response(403);
        $response->setBody("YOU SHALL NOT PASS!");
        return $response;
    });
    $router->setErrorHandler(404, new \MyApi\NotFoundHandler());

You can also set multiple error handlers_ at once by passing a hash array to ``Router::setErrorHandlers``. The hash array must have integer keys representing status codes and handlers_ as values.

.. code-block:: php

    $router->setErrorHandlers([
        400 => "\\MyApi\\BadRequestHandler",
        401 => function () {
           return new \MyApi\UnauthorizedHandler();
        },
        403 => function () {
            $response = new \pjdietz\WellRESTed\Response(403);
            $response->setBody("YOU SHALL NOT PASS!");
            return $response;
        },
        404 => new \MyApi\NotFoundHandler()
    ]);

.. note::

    Only one error handler_ may be registered for a given status code. A subsequent call to set the handler for a given status code will replace the previous handler with the new handler.

Registering a ``404`` handler_ will set the default behavior for when no routes in the router match. A request for ``/birds/`` using the following router will provide a response with a ``404 Not Found`` status and a message body of "I can't find anything at /birds/".

.. code-block:: php

    $router = new \pjdietz\WellRESTed\Router();
    $router->add(
        ["/cats/", $catHandler],
        ["/dogs/", $dogHandler]
    );
    $router->setErrorHandler(404, function ($rqst, $args) {
        $resp = new \pjdietz\WellRESTed\Response(404);
        $resp->setBody("I can't find anything at " . $rqst->getPath());
        return $resp;
    })
    $router->respond();

HTTP Exceptions
^^^^^^^^^^^^^^^

When things go wrong, you can return responses with error codes from any of you handlers_, or you can throw an ``HttpException``. The router will catch any exceptions of this type and provide a response with the corresponding status code.

.. code-block:: php

    $router->add("/cats/{catId}", function ($rqst, $args) {

        // Find a cat in the cat repository.
        $catProvider = new CatProvider();
        $cat = $catProvider->getCatById($args["catId");

        // Throw a NotFoundException if $cat is null.
        if (is_null($cat)) {
            throw new \pjdietz\WellRESTed\Exceptions\HttpExceptions\NotFoundException();
        }

        // Do cat stuff and return a response...
        // ...

    });

The HttpExceptions are all in the ``\pjdietz\WellRESTed\Exceptions\HttpExceptions`` namespace and all inherit from ``HttpException``. Here's the list of exceptions and their status codes.

=========== =========
Status Code Exception
=========== =========
400         BadRequestException
401         UnauthorizedException
403         ForbiddenException
404         NotFoundException
405         MethodNotAllowed
409         ConflictException
410         GoneException
500         HttpException
=========== =========

If you need to trigger an error other than these, throw ``HttpException`` and set the code, and optionally, the message.

.. code-block:: php

    throw new \pjdietz\WellRESTed\Exceptions\HttpExceptions\HttpException("Request Timeout", 408);

Nested Routers
^^^^^^^^^^^^^^

For large sites, you may want to break your router into multiple subrouters. Since ``Router`` implements HandlerInterface_, you can use ``Router`` instances as handlers_. Here are a couple patterns for using subrouters.

Using Router Subclasses
-----------------------

One way to build subrouters is by subclassing ``Router`` for each subsection of your API. By subclassing, you can define a router that populates itself with routes on instantiation, and is able to be instantiated by a top-level router.

Here's a top-level router that directs traffic starting with  ``/cats/`` to ``MyApi\CatRouter`` and traffic starting with ``/dogs/`` to ``MyApi\DogRouter``.

.. code-block:: php

    $router = new \pjdietz\WellRESTed\Router();
    $router->add(
        ["/cats/*", "\\MyApi\\CatRouter"],
        ["/dogs/*", "\\MyApi\\DogRouter"]
    );

Here are router subclasses that contain only routes beginning with the expected prefixes.

.. code-block:: php

    namesapce MyApi;

    class CatRouter extends \pjdietz\WelRESTed\Router
    {
        public function __construct()
        {
            parent::__construct();
            $ns = __NAMESPACE__;
            $this->add([
                "/cats/", "$ns\\CatRootHandler",
                "/cats/{id}", "$ns\\CatItemHandler",
                // ... other handles related to cats...
            ]);
        }
    }

.. code-block:: php

    namesapce MyApi;

    class DogRouter extends \pjdietz\WelRESTed\Router
    {
        public function __construct()
        {
            parent::__construct();
            $ns = __NAMESPACE__;
            $this->add([
                "/dogs/", "$ns\\DogRootHandler",
                "/dogs/{group}/", "$ns\\DogGroupHandler",
                "/dogs/{group}/{breed}", "$ns\\DogBreedHandler",
                // ... other handles related to dogs...
            ]);
        }
    }

With this setup, the top-level router will autoload and instantiate a ``CatHandler`` or ``DogHandler`` only if the request matches, then dispatch the request to the newly instantiated subrouter.

Using a Dependency Container
----------------------------

A second approach to subrouters is to use a dependency container such a Pimple_. A container like Pimple allows you to create "providers" that instantiate and return instances of your various routers and handlers_ as needed. As with the subclassing patten, this pattern delays autoloading and instantiating the classes until they are actually used.

.. code-block:: php

    $c = new Pimple\Container();

    // Create a provider for the top-level router.
    // This will return an instance.
    $c["router"] = (function ($c) {
        $router = new \pjdietz\WellRESTed\Router();
        $router->add(
            ["/cats/*", $c["catRouter"]],
            ["/dogs/*", $c["dogRouter"]]
        );
        return $router;
    });

    // Create "protected" providers for the subrouters.
    // These will return callables that will return the routers when called.
    $c["catRouter"] = $c->protect(function () use ($c) {
        $router = new \pjdietz\WellRESTed\Router();
        $router->add(
            "/cats/", $c["catRootHandler"],
            "/cats/{id}", $c["catItemHandler"],
            // ... other handles related to cats...
        ]);
        return $router;
    });

    $c["dogRouter"] = $c->protect(function () use ($c) {
        $router = new \pjdietz\WellRESTed\Router();
        $router->add(
            "/dogs/", $c["dogRootHandler"],
            "/dogs/{group}/", $c["dogGroupHandler"],
            "/dogs/{group}/{breed}", $c["dogBreedHandler"],
            // ... other handles related to dogs...
        ]);
        return $router;
    });

    // ... Handlers like catRootHandler have protected providers as well.

See `Dependency Injection`_ for more information.

.. _Dependency Injection: dependency-injection.html
.. _handler: Handlers_
.. _Handlers: handlers.html
.. _HandlerInterface: handlers.html#handlerinterface
.. _Pimple: http://pimple.sensiolabs.org
.. _Requests: requests.html
.. _Responses: responses.html
.. _Routes: routes.html
.. _Specifying Handlers: #specifying-handlers
