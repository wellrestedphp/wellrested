<?php

namespace WellRESTed\Test\Unit\Routing\Route;

use Prophecy\Argument;
use WellRESTed\Routing\Route\PrefixRoute;
use WellRESTed\Routing\Route\RouteInterface;

/**
 * @coversDefaultClass WellRESTed\Routing\Route\PrefixRoute
 * @uses WellRESTed\Routing\Route\PrefixRoute
 * @uses WellRESTed\Routing\Route\Route
 * @group route
 * @group routing
 */
class PrefixRouteTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::__construct
     */
    public function testTrimsAsteriskFromEndOfTarget()
    {
        $methodMap = $this->prophesize('WellRESTed\Routing\MethodMapInterface');
        $route = new PrefixRoute("/cats/*", $methodMap->reveal());
        $this->assertEquals("/cats/", $route->getTarget());
    }

    /**
     * @covers ::getType
     */
    public function testReturnsPrefixType()
    {
        $methodMap = $this->prophesize('WellRESTed\Routing\MethodMapInterface');
        $route = new PrefixRoute("/*", $methodMap->reveal());
        $this->assertSame(RouteInterface::TYPE_PREFIX, $route->getType());
    }

    /**
     * @covers ::matchesRequestTarget
     */
    public function testMatchesExactRequestTarget()
    {
        $methodMap = $this->prophesize('WellRESTed\Routing\MethodMapInterface');
        $route = new PrefixRoute("/*", $methodMap->reveal());
        $this->assertTrue($route->matchesRequestTarget("/"));
    }

    /**
     * @covers ::matchesRequestTarget
     */
    public function testMatchesRequestTargetWithSamePrefix()
    {
        $methodMap = $this->prophesize('WellRESTed\Routing\MethodMapInterface');
        $route = new PrefixRoute("/*", $methodMap->reveal());
        $this->assertTrue($route->matchesRequestTarget("/cats/"));
    }

    /**
     * @covers ::matchesRequestTarget
     */
    public function testDoesNotMatchNonmatchingRequestTarget()
    {
        $methodMap = $this->prophesize('WellRESTed\Routing\MethodMapInterface');
        $route = new PrefixRoute("/animals/cats/", $methodMap->reveal());
        $this->assertFalse($route->matchesRequestTarget("/animals/dogs/"));
    }
}
