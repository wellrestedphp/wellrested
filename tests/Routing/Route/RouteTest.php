<?php

namespace WellRESTed\Routing\Route;

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Server\RequestHandlerInterface;
use WellRESTed\Message\Response;
use WellRESTed\Message\ServerRequest;
use WellRESTed\Test\TestCase;

class RouteTest extends TestCase
{
    use ProphecyTrait;

    private const TARGET = '/target';

    private $methodMap;
    private $route;

    protected function setUp(): void
    {
        $this->methodMap = $this->prophesize(MethodMap::class);
        $this->methodMap->register(Argument::cetera());
        $this->methodMap->__invoke(Argument::cetera())
            ->willReturn(new Response());

        $this->route = new StaticRoute(
            self::TARGET,
            $this->methodMap->reveal()
        );
    }

    public function testReturnsTarget(): void
    {
        $this->assertSame(self::TARGET, $this->route->getTarget());
    }

    public function testRegistersDispatchableWithMethodMap(): void
    {
        $handler = $this->prophesize(RequestHandlerInterface::class)->reveal();

        $this->route->register('GET', $handler);

        $this->methodMap->register('GET', $handler)->shouldHaveBeenCalled();
    }

    public function testDispatchesMethodMap(): void
    {
        $request = new ServerRequest();
        $response = new Response();
        $next = function ($rqst, $resp) {
            return $resp;
        };

        call_user_func($this->route, $request, $response, $next);

        $this->methodMap->__invoke(Argument::cetera())->shouldHaveBeenCalled();
    }

    public function testProvidesMapOfMethodsToHandlers(): void
    {
        // Arrange
        $map = ['GET' => new Response()];
        $this->methodMap->getMethods()->willReturn($map);
        // Act
        $methods = $this->route->getMethods();
        // Assert
        $this->assertEquals($map, $methods);
    }
}
