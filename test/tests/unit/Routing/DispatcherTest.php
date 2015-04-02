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
        $this->response->withStatus(Argument::any())->willReturn($this->response->reveal());
    }

    public function testDispatchedCallable()
    {
        $middleware = function ($request, &$response) {
            $response = $response->withStatus(200);
        };
        $dispatcher = new Dispatcher();
        $response = $this->response->reveal();
        $dispatcher->dispatch($middleware, $this->request->reveal(), $response);
        $this->response->withStatus(200)->shouldHaveBeenCalled();
        $this->assertSame($this->response->reveal(), $response);
    }

    public function testDispatchedFromCallable()
    {
        $middleware = function () {
            return new DispatcherTest_Middleware();
        };
        $response = $this->response->reveal();
        $dispatcher = new Dispatcher();
        $dispatcher->dispatch($middleware, $this->request->reveal(), $response);
        $this->response->withStatus(200)->shouldHaveBeenCalled();
        $this->assertSame($this->response->reveal(), $response);
    }

    public function testDispatchedFromString()
    {
        $middleware = __NAMESPACE__ . "\\DispatcherTest_Middleware";
        $response = $this->response->reveal();
        $dispatcher = new Dispatcher();
        $dispatcher->dispatch($middleware, $this->request->reveal(), $response);
        $this->response->withStatus(200)->shouldHaveBeenCalled();
        $this->assertSame($this->response->reveal(), $response);
    }

    public function testDispatchedInstance()
    {
        $middleware = new DispatcherTest_Middleware();
        $dispatcher = new Dispatcher();
        $response = $this->response->reveal();
        $dispatcher->dispatch($middleware, $this->request->reveal(), $response);
        $this->response->withStatus(200)->shouldHaveBeenCalled();
        $this->assertSame($this->response->reveal(), $response);
    }
}

class DispatcherTest_Middleware implements MiddlewareInterface
{
    public function dispatch(ServerRequestInterface $request, ResponseInterface &$response)
    {
        $response = $response->withStatus(200);
    }
}
