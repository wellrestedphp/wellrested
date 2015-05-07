<?php

namespace WellRESTed\Test\Unit\Routing;

use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WellRESTed\Routing\Dispatcher;
use WellRESTed\Routing\MiddlewareInterface;

/**
 * @covers WellRESTed\Routing\Dispatcher
 */
class DispatcherTest extends \PHPUnit_Framework_TestCase
{
    private $request;
    private $response;

    public function setUp()
    {
        $this->request = $this->prophesize("\\Psr\\Http\\Message\\ServerRequestInterface");
        $this->response = $this->prophesize("\\Psr\\Http\\Message\\ResponseInterface");
        $this->response->withStatus(Argument::any())->will(
            function ($args) {
                $this->getStatusCode()->willReturn($args[0]);
                return $this;
            }
        );
    }

    public function testDispatchesCallable()
    {
        $middleware = function ($request, &$response) {
            $response = $response->withStatus(200);
        };

        $dispatcher = new Dispatcher();
        $request = $this->request->reveal();
        $response = $this->response->reveal();
        $dispatcher->dispatch($middleware, $request, $response);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testDispatchesMiddlewareInstanceFromCallable()
    {
        $middleware = function () {
            return new DispatcherTest_Middleware();
        };

        $dispatcher = new Dispatcher();
        $request = $this->request->reveal();
        $response = $this->response->reveal();
        $dispatcher->dispatch($middleware, $request, $response);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testDispatchesMiddlewareFromClassNameString()
    {
        $middleware = __NAMESPACE__ . "\\DispatcherTest_Middleware";

        $dispatcher = new Dispatcher();
        $request = $this->request->reveal();
        $response = $this->response->reveal();
        $dispatcher->dispatch($middleware, $request, $response);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testDispatchesMiddlewareInstance()
    {
        $middleware = new DispatcherTest_Middleware();

        $dispatcher = new Dispatcher();
        $request = $this->request->reveal();
        $response = $this->response->reveal();
        $dispatcher->dispatch($middleware, $request, $response);

        $this->assertEquals(200, $response->getStatusCode());
    }
}

class DispatcherTest_Middleware implements MiddlewareInterface
{
    public function dispatch(ServerRequestInterface $request, ResponseInterface &$response)
    {
        $response = $response->withStatus(200);
    }
}
