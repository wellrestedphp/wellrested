<?php

namespace WellRESTed\Test\Unit\Dispatching;

use Prophecy\Argument;
use WellRESTed\Dispatching\DispatchStack;

/**
 * @coversDefaultClass WellRESTed\Dispatching\DispatchStack
 * @uses WellRESTed\Dispatching\DispatchStack
 * @group dispatching
 */
class DispatchStackTest extends \PHPUnit_Framework_TestCase
{
    private $request;
    private $response;
    private $next;
    private $dispatcher;

    public function setUp()
    {
        parent::setUp();
        $this->request = $this->prophesize('Psr\Http\Message\ServerRequestInterface');
        $this->response = $this->prophesize('Psr\Http\Message\ResponseInterface');
        $this->next = function ($request, $response) {
            return $response;
        };
        $this->dispatcher = $this->prophesize('WellRESTed\Dispatching\DispatcherInterface');
        $this->dispatcher->dispatch(Argument::cetera())->will(function ($args) {
            list($middleware, $request, $response, $next) = $args;
            return $middleware($request, $response, $next);
        });
    }

    /**
     * @covers ::__construct
     */
    public function testCreatesInstance()
    {
        $stack = new DispatchStack($this->dispatcher->reveal());
        $this->assertNotNull($stack);
    }

    /**
     * @covers ::add
     */
    public function testAddIsFluid()
    {
        $stack = new DispatchStack($this->dispatcher->reveal());
        $this->assertSame($stack, $stack->add("middleware1"));
    }

    /**
     * @covers ::dispatch
     * @covers ::getCallableChain
     */
    public function testDispachesMiddlewareInOrderAdded()
    {
        // Each middelware will add its "name" to this array.
        $callOrder = [];

        $stack = new DispatchStack($this->dispatcher->reveal());
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

    /**
     * @covers ::dispatch
     */
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

        $stack = new DispatchStack($this->dispatcher->reveal());
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

        $stack = new DispatchStack($this->dispatcher->reveal());
        $stack->dispatch($this->request->reveal(), $this->response->reveal(), $next);
        $this->assertTrue($nextCalled);
    }
}
