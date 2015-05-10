<?php

namespace WellRESTed\Test\Unit\Routing\Route;

use Prophecy\Argument;
use WellRESTed\Routing\Route\RouteInterface;
use WellRESTed\Routing\Route\StaticRoute;

/**
 * @coversDefaultClass WellRESTed\Routing\Route\StaticRoute
 * @uses WellRESTed\Routing\Route\StaticRoute
 * @uses WellRESTed\Routing\Route\Route
 * @group route
 * @group routing
 */
class StaticRouteTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::getType
     */
    public function testReturnsStaticType()
    {
        $methodMap = $this->prophesize('WellRESTed\Routing\MethodMapInterface');
        $route = new StaticRoute("/", $methodMap->reveal());
        $this->assertSame(RouteInterface::TYPE_STATIC, $route->getType());
    }

    /**
     * @covers ::matchesRequestTarget
     */
    public function testMatchesExactRequestTarget()
    {
        $methodMap = $this->prophesize('WellRESTed\Routing\MethodMapInterface');
        $route = new StaticRoute("/", $methodMap->reveal());
        $this->assertTrue($route->matchesRequestTarget("/"));
    }

    /**
     * @covers ::matchesRequestTarget
     */
    public function testDoesNotMatchNonmatchingRequestTarget()
    {
        $methodMap = $this->prophesize('WellRESTed\Routing\MethodMapInterface');
        $route = new StaticRoute("/", $methodMap->reveal());
        $this->assertFalse($route->matchesRequestTarget("/cats/"));
    }
}
