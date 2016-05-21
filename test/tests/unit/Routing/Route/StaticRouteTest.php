<?php

namespace WellRESTed\Test\Unit\Routing\Route;

use Prophecy\Argument;
use WellRESTed\Routing\Route\RouteInterface;
use WellRESTed\Routing\Route\StaticRoute;

/**
 * @covers WellRESTed\Routing\Route\StaticRoute
 * @group route
 * @group routing
 */
class StaticRouteTest extends \PHPUnit_Framework_TestCase
{
    public function testReturnsStaticType()
    {
        $methodMap = $this->prophesize('WellRESTed\Routing\MethodMapInterface');
        $route = new StaticRoute("/", $methodMap->reveal());
        $this->assertSame(RouteInterface::TYPE_STATIC, $route->getType());
    }

    public function testMatchesExactRequestTarget()
    {
        $methodMap = $this->prophesize('WellRESTed\Routing\MethodMapInterface');
        $route = new StaticRoute("/", $methodMap->reveal());
        $this->assertTrue($route->matchesRequestTarget("/"));
    }

    public function testReturnsEmptyArrayForPathVariables()
    {
        $methodMap = $this->prophesize('WellRESTed\Routing\MethodMapInterface');
        $route = new StaticRoute("/", $methodMap->reveal());
        $this->assertSame([], $route->getPathVariables());
    }

    public function testDoesNotMatchNonmatchingRequestTarget()
    {
        $methodMap = $this->prophesize('WellRESTed\Routing\MethodMapInterface');
        $route = new StaticRoute("/", $methodMap->reveal());
        $this->assertFalse($route->matchesRequestTarget("/cats/"));
    }
}
