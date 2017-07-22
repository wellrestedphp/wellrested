<?php

namespace WellRESTed\Test\Unit\Dispatching;

use WellRESTed\Dispatching\Dispatcher;
use WellRESTed\Dispatching\DispatchStack;
use WellRESTed\Message\Response;
use WellRESTed\Message\ServerRequest;
use WellRESTed\Test\Doubles\NextMock;
use WellRESTed\Test\TestCase;

class DispatchStackTest extends TestCase
{
    private $request;
    private $response;
    private $next;

    public function setUp()
    {
        parent::setUp();
        $this->request = new ServerRequest();
        $this->response = new Response();
        $this->next = new NextMock();
    }

    public function testDispatchesMiddlewareInOrderAdded()
    {
        // Each middleware will add its "name" to this array.
        $callOrder = [];
        $stack = new DispatchStack(new Dispatcher());
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
        $stack($this->request, $this->response, $this->next);
        $this->assertEquals(["first", "second", "third"], $callOrder);
    }

    public function testCallsNextAfterDispatchingEmptyStack()
    {
        $stack = new DispatchStack(new Dispatcher());
        $stack($this->request, $this->response, $this->next);
        $this->assertTrue($this->next->called);
    }

    public function testCallsNextAfterDispatchingStack()
    {
        $middleware = function ($request, $response, $next) use (&$callOrder) {
            return $next($request, $response);
        };

        $stack = new DispatchStack(new Dispatcher());
        $stack->add($middleware);
        $stack->add($middleware);
        $stack->add($middleware);

        $stack($this->request, $this->response, $this->next);
        $this->assertTrue($this->next->called);
    }

    public function testDoesNotCallNextWhenStackStopsEarly()
    {
        $middlewareGo = function ($request, $response, $next) use (&$callOrder) {
            return $next($request, $response);
        };
        $middlewareStop = function ($request, $response, $next) use (&$callOrder) {
            return $response;
        };

        $stack = new DispatchStack(new Dispatcher());
        $stack->add($middlewareGo);
        $stack->add($middlewareStop);
        $stack->add($middlewareStop);

        $stack($this->request, $this->response, $this->next);
        $this->assertFalse($this->next->called);
    }
}
