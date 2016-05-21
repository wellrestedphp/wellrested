<?php

namespace WellRESTed\Test\Unit\Routing\Route;

use Prophecy\Argument;
use WellRESTed\Routing\Route\PrefixRoute;
use WellRESTed\Routing\Route\RouteInterface;

/**
 * @covers WellRESTed\Routing\Route\PrefixRoute
 * @group route
 * @group routing
 */
class PrefixRouteTest extends \PHPUnit_Framework_TestCase
{
    public function testTrimsAsteriskFromEndOfTarget()
    {
        $methodMap = $this->prophesize('WellRESTed\Routing\MethodMapInterface');
        $route = new PrefixRoute("/cats/*", $methodMap->reveal());
        $this->assertEquals("/cats/", $route->getTarget());
    }

    public function testReturnsPrefixType()
    {
        $methodMap = $this->prophesize('WellRESTed\Routing\MethodMapInterface');
        $route = new PrefixRoute("/*", $methodMap->reveal());
        $this->assertSame(RouteInterface::TYPE_PREFIX, $route->getType());
    }

    public function testReturnsEmptyArrayForPathVariables()
    {
        $methodMap = $this->prophesize('WellRESTed\Routing\MethodMapInterface');
        $route = new PrefixRoute("/*", $methodMap->reveal());
        $this->assertSame([], $route->getPathVariables());
    }

    public function testMatchesExactRequestTarget()
    {
        $methodMap = $this->prophesize('WellRESTed\Routing\MethodMapInterface');
        $route = new PrefixRoute("/*", $methodMap->reveal());
        $this->assertTrue($route->matchesRequestTarget("/"));
    }

    public function testMatchesRequestTargetWithSamePrefix()
    {
        $methodMap = $this->prophesize('WellRESTed\Routing\MethodMapInterface');
        $route = new PrefixRoute("/*", $methodMap->reveal());
        $this->assertTrue($route->matchesRequestTarget("/cats/"));
    }

    public function testDoesNotMatchNonmatchingRequestTarget()
    {
        $methodMap = $this->prophesize('WellRESTed\Routing\MethodMapInterface');
        $route = new PrefixRoute("/animals/cats/", $methodMap->reveal());
        $this->assertFalse($route->matchesRequestTarget("/animals/dogs/"));
    }
}
