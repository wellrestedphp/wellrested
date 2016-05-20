<?php

namespace WellRESTed\Test\Unit\Dispatching;

use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WellRESTed\Dispatching\Dispatcher;
use WellRESTed\Message\Response;
use WellRESTed\Message\ServerRequest;
use WellRESTed\MiddlewareInterface;
use WellRESTed\Test\NextSpy;

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
        $this->request = new ServerRequest();
        $this->response = new Response();
        $this->next = new NextSpy();
    }

    public function testDispatchesCallableThatReturnsResponse()
    {
        $middleware = function ($request, $response, $next) {
            return $next($request, $response->withStatus(200));
        };

        $dispatcher = new Dispatcher();
        $response = $dispatcher->dispatch($middleware, $this->request, $this->response, $this->next);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testDispatchesMiddlewareInstanceFromCallable()
    {
        $middleware = function () {
            return new DispatcherTest_Middleware();
        };

        $dispatcher = new Dispatcher();
        $response = $dispatcher->dispatch($middleware, $this->request, $this->response, $this->next);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testDispatchesMiddlewareFromClassNameString()
    {
        $middleware = __NAMESPACE__ . '\DispatcherTest_Middleware';

        $dispatcher = new Dispatcher();
        $response = $dispatcher->dispatch($middleware, $this->request, $this->response, $this->next);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testDispatchesMiddlewareInstance()
    {
        $middleware = new DispatcherTest_Middleware();

        $dispatcher = new Dispatcher();
        $response = $dispatcher->dispatch($middleware, $this->request, $this->response, $this->next);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @uses WellRESTed\Dispatching\DispatchStack
     */
    public function testDispatchesArrayAsDispatchStack()
    {
        $middleware = new DispatcherTest_Middleware();

        $dispatcher = new Dispatcher();
        $response = $dispatcher->dispatch([$middleware], $this->request, $this->response, $this->next);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @expectedException \WellRESTed\Dispatching\DispatchException
     */
    public function testThrowsExceptionWhenUnableToDispatch()
    {
        $middleware = null;

        $dispatcher = new Dispatcher();
        $dispatcher->dispatch($middleware, $this->request, $this->response, $this->next);
    }
}

class DispatcherTest_Middleware implements MiddlewareInterface
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next)
    {
        $response = $response->withStatus(200);
        return $next($request, $response);
    }
}
