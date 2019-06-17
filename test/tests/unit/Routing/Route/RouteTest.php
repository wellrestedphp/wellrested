<?php

namespace WellRESTed\Test\Unit\Routing\Route;

use Prophecy\Argument;
use Psr\Http\Server\RequestHandlerInterface;
use WellRESTed\Message\Response;
use WellRESTed\Message\ServerRequest;
use WellRESTed\Routing\Route\MethodMap;
use WellRESTed\Routing\Route\StaticRoute;
use WellRESTed\Test\TestCase;

class RouteTest extends TestCase
{
    const TARGET = '/target';

    private $methodMap;
    private $route;

    public function setUp(): void
    {
        $this->methodMap = $this->prophesize(MethodMap::class);
        $this->methodMap->register(Argument::cetera())
            ->willReturn();
        $this->methodMap->__invoke(Argument::cetera())
            ->willReturn(new Response());

        $this->route = new StaticRoute(
            self::TARGET, $this->methodMap->reveal());
    }

    public function testReturnsTarget()
    {
        $this->assertSame(self::TARGET, $this->route->getTarget());
    }

    public function testRegistersDispatchableWithMethodMap()
    {
        $handler = $this->prophesize(RequestHandlerInterface::class)->reveal();

        $this->route->register('GET', $handler);

        $this->methodMap->register('GET', $handler)->shouldHaveBeenCalled();
    }

    public function testDispatchesMethodMap()
    {
        $request = new ServerRequest();
        $response = new Response();
        $next = function ($rqst, $resp) {
            return $resp;
        };

        $this->route->__invoke($request, $response, $next);

        $this->methodMap->__invoke(Argument::cetera())->shouldHaveBeenCalled();
    }
}
