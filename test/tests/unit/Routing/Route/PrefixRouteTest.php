<?php

namespace WellRESTed\Routing\Route;

use Prophecy\PhpUnit\ProphecyTrait;
use WellRESTed\Test\TestCase;

class PrefixRouteTest extends TestCase
{
    use ProphecyTrait;

    public function testTrimsAsteriskFromEndOfTarget(): void
    {
        $methodMap = $this->prophesize(MethodMap::class);
        $route = new PrefixRoute('/cats/*', $methodMap->reveal());
        $this->assertEquals('/cats/', $route->getTarget());
    }

    public function testReturnsPrefixType(): void
    {
        $methodMap = $this->prophesize(MethodMap::class);
        $route = new PrefixRoute('/*', $methodMap->reveal());
        $this->assertSame(Route::TYPE_PREFIX, $route->getType());
    }

    public function testReturnsEmptyArrayForPathVariables(): void
    {
        $methodMap = $this->prophesize(MethodMap::class);
        $route = new PrefixRoute('/*', $methodMap->reveal());
        $this->assertSame([], $route->getPathVariables());
    }

    public function testMatchesExactRequestTarget(): void
    {
        $methodMap = $this->prophesize(MethodMap::class);
        $route = new PrefixRoute('/*', $methodMap->reveal());
        $this->assertTrue($route->matchesRequestTarget('/'));
    }

    public function testMatchesRequestTargetWithSamePrefix(): void
    {
        $methodMap = $this->prophesize(MethodMap::class);
        $route = new PrefixRoute('/*', $methodMap->reveal());
        $this->assertTrue($route->matchesRequestTarget('/cats/'));
    }

    public function testDoesNotMatchNonMatchingRequestTarget(): void
    {
        $methodMap = $this->prophesize(MethodMap::class);
        $route = new PrefixRoute('/animals/cats/', $methodMap->reveal());
        $this->assertFalse($route->matchesRequestTarget('/animals/dogs/'));
    }
}
