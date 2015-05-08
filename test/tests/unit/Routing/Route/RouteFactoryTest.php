<?php

namespace WellRESTed\Test\Unit\Routing\Route;

use Prophecy\Argument;
use WellRESTed\Routing\Route\RouteFactory;
use WellRESTed\Routing\Route\RouteInterface;

/**
 * @covers WellRESTed\Routing\Route\RouteFactory
 * @uses WellRESTed\Routing\Route\TemplateRoute
 * @uses WellRESTed\Routing\Route\RegexRoute
 * @uses WellRESTed\Routing\Route\PrefixRoute
 * @uses WellRESTed\Routing\Route\StaticRoute
 * @uses WellRESTed\Routing\Route\Route
 * @uses WellRESTed\Routing\MethodMap
 */
class RouteFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreatesStaticRoute()
    {
        $factory = new RouteFactory();
        $route = $factory->create("/cats/");
        $this->assertSame(RouteInterface::TYPE_STATIC, $route->getType());
    }

    public function testCreatesPrefixRoute()
    {
        $factory = new RouteFactory();
        $route = $factory->create("/cats/*");
        $this->assertSame(RouteInterface::TYPE_PREFIX, $route->getType());
    }

    public function testCreatesRegexRoute()
    {
        $factory = new RouteFactory();
        $route = $factory->create("~/cat/[0-9]+~");
        $this->assertSame(RouteInterface::TYPE_PATTERN, $route->getType());
    }

    public function testCreatesTemplateRoute()
    {
        $factory = new RouteFactory();
        $route = $factory->create("/cat/{id}");
        $this->assertSame(RouteInterface::TYPE_PATTERN, $route->getType());
    }
}
