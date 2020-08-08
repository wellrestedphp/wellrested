<?php

namespace WellRESTed\Test\Unit\Routing\Route;

use Prophecy\PhpUnit\ProphecyTrait;
use WellRESTed\Routing\Route\MethodMap;
use WellRESTed\Routing\Route\PrefixRoute;
use WellRESTed\Routing\Route\RouteInterface;
use WellRESTed\Test\TestCase;

class PrefixRouteTest extends TestCase
{
    use ProphecyTrait;
    
    public function testTrimsAsteriskFromEndOfTarget()
    {
        $methodMap = $this->prophesize(MethodMap::class);
        $route = new PrefixRoute('/cats/*', $methodMap->reveal());
        $this->assertEquals('/cats/', $route->getTarget());
    }

    public function testReturnsPrefixType()
    {
        $methodMap = $this->prophesize(MethodMap::class);
        $route = new PrefixRoute('/*', $methodMap->reveal());
        $this->assertSame(RouteInterface::TYPE_PREFIX, $route->getType());
    }

    public function testReturnsEmptyArrayForPathVariables()
    {
        $methodMap = $this->prophesize(MethodMap::class);
        $route = new PrefixRoute('/*', $methodMap->reveal());
        $this->assertSame([], $route->getPathVariables());
    }

    public function testMatchesExactRequestTarget()
    {
        $methodMap = $this->prophesize(MethodMap::class);
        $route = new PrefixRoute('/*', $methodMap->reveal());
        $this->assertTrue($route->matchesRequestTarget('/'));
    }

    public function testMatchesRequestTargetWithSamePrefix()
    {
        $methodMap = $this->prophesize(MethodMap::class);
        $route = new PrefixRoute('/*', $methodMap->reveal());
        $this->assertTrue($route->matchesRequestTarget('/cats/'));
    }

    public function testDoesNotMatchNonmatchingRequestTarget()
    {
        $methodMap = $this->prophesize(MethodMap::class);
        $route = new PrefixRoute('/animals/cats/', $methodMap->reveal());
        $this->assertFalse($route->matchesRequestTarget('/animals/dogs/'));
    }
}
