Dependency Injection
====================

WellRESTed strives to play nicely with other code and not force developers into using any specific libraries or frameworks. As such, WellRESTed does not provide a dependency injection container, nor does it require you to use a specific container (or any).

This section describes the recommended way of using WellRESTed with Pimple_, a common dependency injection container for PHP.

Imaging we have a ``FooHandler`` that depends on a ``BarInterface``, and ``BazInterface``. Our handler looks something like this:

.. code-block:: php

    class FooHandler implements RequestHandlerInterface
    {
        private $bar;
        private $baz;

        public function __construct(BarInterface $bar, BazInterface $bar)
        {
            $this->bar = $bar;
            $this->baz = $baz;
        }

        public function handle(ServerRequestInterface $request): ResponseInterface
        {
            // Do something with the bar and baz and return a response...
            // ...
        }
    }

We can register the handler and these dependencies in a Pimple_ service provider.

.. code-block:: php

    class MyServiceProvider implements ServiceProviderInterface
    {
        public function register(Container $c) 
        {
            // Register the Bar and Baz as services.
            $c['bar'] = function ($c) {
                return new Bar();
            };
            $c['baz'] = function ($c) {
                return new Baz();
            };

            // Register the Handler as a protected function. When you use
            // protect, Pimple returns the function itself instead of the return
            // value of the function.
            $c['fooHandler'] = $c->protect(function () use ($c) {
                return new FooHandler($c['bar'], $c['baz']);
            });
        }
    }

By "protected" the ``fooHandler`` service, we are delaying the instantiation of the ``FooHandler``, the ``Bar``, and the ``Baz`` until the handler needs to be dispatched. This works because we're not passing instance of ``FooHandler`` when we register this with a router, we're passing a function to it that does the instantiation on demand.

.. _Pimple: https://pimple.symfony.com/
