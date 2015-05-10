<?php

namespace WellRESTed\Test\Unit\Routing;

use Prophecy\Argument;
use WellRESTed\Routing\DispatchStack;

/**
 * @coversDefaultClass WellRESTed\Routing\DispatchStack
 * @uses WellRESTed\Routing\DispatchStack
 * @uses WellRESTed\Routing\Dispatcher
 */
class DispatchStackTest extends \PHPUnit_Framework_TestCase
{
    private $request;
    private $response;
    private $next;

    public function setUp()
    {
        parent::setUp();
        $this->request = $this->prophesize('Psr\Http\Message\ServerRequestInterface');
        $this->response = $this->prophesize('Psr\Http\Message\ResponseInterface');
        $this->next = function ($request, $response) {
            return $response;
        };
    }

    /**
     * @covers ::__construct
     * @covers ::getDispatcher
     */
    public function testCreatesInstance()
    {
        $stack = new DispatchStack();
        $this->assertNotNull($stack);
    }

    /**
     * @covers ::add
     */
    public function testAddIsFluid()
    {
        $stack = new DispatchStack();
        $this->assertSame($stack, $stack->add("middleware1"));
    }

    /**
     * @covers ::dispatch
     */
    public function testDispachesMiddlewareInOrderAdded()
    {
        // Each middelware will add its "name" to this array.
        $callOrder = [];

        $stack = new DispatchStack();
        $stack->add(function ($request, $response, $next) use (&$callOrder) {
            $callOrder[] = "first";
            return $next($request, $response);
        });
        $stack->add(function ($request, $response, $next) use (&$callOrder) {
            $callOrder[] = "second";
            return $next($request, $response);
        });
        $stack->add(function ($request, $response, $next) use (&$callOrder) {
            $callOrder[] = "third";
            return $next($request, $response);
        });
        $stack->dispatch($this->request->reveal(), $this->response->reveal(), $this->next);
        $this->assertEquals(["first", "second", "third"], $callOrder);
    }

    public function testCallsNextAfterDispatchingStack()
    {
        $nextCalled = false;
        $next = function ($request, $response) use (&$nextCalled) {
            $nextCalled = true;
            return $response;
        };

        $middleware = function ($request, $response, $next) use (&$callOrder) {
            return $next($request, $response);
        };

        $stack = new DispatchStack();
        $stack->add($middleware);
        $stack->add($middleware);
        $stack->add($middleware);

        $stack->dispatch($this->request->reveal(), $this->response->reveal(), $next);
        $this->assertTrue($nextCalled);
    }

    /**
     * @covers ::dispatch
     */
    public function testCallsNextAfterDispatchingEmptyStack()
    {
        $nextCalled = false;
        $next = function ($request, $response) use (&$nextCalled) {
            $nextCalled = true;
            return $response;
        };

        $stack = new DispatchStack();
        $stack->dispatch($this->request->reveal(), $this->response->reveal(), $next);
        $this->assertTrue($nextCalled);
    }
}
