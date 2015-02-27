Dependency Injection
====================

Here are a few strategies for how to do dependency injection with WellRESTed.

HandlerInterface::getResponse
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

You can inject dependencies into your handlers_ by passing them into ``Router::respond`` (or ``Router::getResponse``). This array will propagate through the routes_ to your handler_, possibly gaining additional array members (like variables from a TemplateRoute_) along the way.

Define a handler_ that expects to receive the dependency container as the "container" element of the array passed to ``getResponse``.

.. code-block:: php

    Class CatHandler implements \pjdietz\WellRESTed\Interfaces\HandlerInterface
    {
        public function getResponse(RequestInterface $request, array $args = null)
        {
            // Extract the container from the second parameter.
            $container = $args["container"];
            // Do something with the container, and make a response.
            // ...
            return $response;
        }
    }

Create the router. Pass the the container to ``Router::respond`` as the "container" array element.

.. code-block:: php

    $container = new MySuperCoolDependencyContainer();
    $router = new \pjdietz\WellRESTed\Router();
    $router->add("/cats", "CatHandler");

    // Pass an array containing the dependencies to Router::respond().
    $router->respond(["container" => container]);


Callables
^^^^^^^^^

When using callables to provide handlers_, you have the opportunity to inject dependencies into the handler's constructor.

.. code-block:: php

    Class CatHandler implements \pjdietz\WellRESTed\Interfaces\HandlerInterface
    {
        private $container;

        public function __construct($container)
        {
            $this->container = $container;
        }

        public function getResponse(RequestInterface $request, array $args = null)
        {
            // Do something with the $this->container, and make a response.
            // ...
            return $response;
        }
    }


Create the router. Pass the the container to the handler upon instantiation.

.. code-block:: php

    $container = new MySuperCoolDependencyContainer();

    $router = new Router();
    $router->add("/cats/", function () use ($container) {
        return new CatHandler($container);
    });
    $router->respond();

For extra fun (and more readable code), you could store the callable that provides the handler in the container. Here's an example using Pimple_).

.. code-block:: php

    $c = new Pimple\Container();
    $c["catHandler"] = $c->protect(function () use ($c) {
        return new CatHandler($c);
    });

    $router = new Router();
    $router->add("/cats/", $c["catHandler"]);
    $router->respond();

.. _Handler: Handlers_
.. _Handlers: handlers.html
.. _Pimple: http://pimple.sensiolabs.org
.. _Routes: routes.html
.. _TemplateRoute: routes.html#template-routes
