<?php

namespace WellRESTed\Test\Unit\Dispatching;

use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WellRESTed\Dispatching\Dispatcher;
use WellRESTed\Routing\MiddlewareInterface;

/**
 * @covers WellRESTed\Dispatching\Dispatcher
 * @group dispatching
 */
class DispatcherTest extends \PHPUnit_Framework_TestCase
{
    private $request;
    private $response;
    private $next;

    public function setUp()
    {
        $this->request = $this->prophesize('Psr\Http\Message\ServerRequestInterface');
        $this->response = $this->prophesize('Psr\Http\Message\ResponseInterface');
        $this->response->withStatus(Argument::any())->will(
            function ($args) {
                $this->getStatusCode()->willReturn($args[0]);
                return $this;
            }
        );
        $this->next = function ($request, $response) {
            return $response;
        };
    }

    public function testDispatchesCallableThatReturnsResponse()
    {
        $middleware = function ($request, $response, $next) {
            return $next($request, $response->withStatus(200));
        };

        $dispatcher = new Dispatcher();
        $response = $dispatcher->dispatch($middleware, $this->request->reveal(), $this->response->reveal(), $this->next);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testDispatchesMiddlewareInstanceFromCallable()
    {
        $middleware = function () {
            return new DispatcherTest_Middleware();
        };

        $dispatcher = new Dispatcher();
        $response = $dispatcher->dispatch($middleware, $this->request->reveal(), $this->response->reveal(), $this->next);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testDispatchesMiddlewareFromClassNameString()
    {
        $middleware = __NAMESPACE__ . '\DispatcherTest_Middleware';

        $dispatcher = new Dispatcher();
        $response = $dispatcher->dispatch($middleware, $this->request->reveal(), $this->response->reveal(), $this->next);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testDispatchesMiddlewareInstance()
    {
        $middleware = new DispatcherTest_Middleware();

        $dispatcher = new Dispatcher();
        $response = $dispatcher->dispatch($middleware, $this->request->reveal(), $this->response->reveal(), $this->next);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testThrowsExceptionWhenUnableToDispatch()
    {
        $middleware = null;

        $dispatcher = new Dispatcher();
        $dispatcher->dispatch($middleware, $this->request->reveal(), $this->response->reveal(), $this->next);
    }
}

class DispatcherTest_Middleware implements MiddlewareInterface
{
    public function dispatch(ServerRequestInterface $request, ResponseInterface $response, $next)
    {
        $response = $response->withStatus(200);
        return $next($request, $response);
    }
}
