<?php

namespace WellRESTed\Routing\Route;

use Prophecy\PhpUnit\ProphecyTrait;
use WellRESTed\Test\TestCase;

class StaticRouteTest extends TestCase
{
    use ProphecyTrait;

    public function testReturnsStaticType(): void
    {
        $methodMap = $this->prophesize(MethodMap::class);
        $route = new StaticRoute('/', $methodMap->reveal());
        $this->assertSame(Route::TYPE_STATIC, $route->getType());
    }

    public function testMatchesExactRequestTarget(): void
    {
        $methodMap = $this->prophesize(MethodMap::class);
        $route = new StaticRoute('/', $methodMap->reveal());
        $this->assertTrue($route->matchesRequestTarget('/'));
    }

    public function testReturnsEmptyArrayForPathVariables(): void
    {
        $methodMap = $this->prophesize(MethodMap::class);
        $route = new StaticRoute('/', $methodMap->reveal());
        $this->assertSame([], $route->getPathVariables());
    }

    public function testDoesNotMatchNonMatchingRequestTarget(): void
    {
        $methodMap = $this->prophesize(MethodMap::class);
        $route = new StaticRoute('/', $methodMap->reveal());
        $this->assertFalse($route->matchesRequestTarget('/cats/'));
    }
}
