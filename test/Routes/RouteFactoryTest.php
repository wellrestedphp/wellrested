<?php

namespace pjdietz\WellRESTed\Test;

use pjdietz\WellRESTed\Routes\RouteFactory;
use Prophecy\Argument;

/**
 * @covers pjdietz\WellRESTed\Routes\RouteFactory
 */
class RouteFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider routeProvider
     */
    public function testCreatesRouteOfCorrectType($path, $expectedType)
    {
        $factory = new RouteFactory();
        $route = $factory->createRoute($path, "\\MyHandler");
        $this->assertInstanceOf($expectedType, $route);
    }

    public function routeProvider()
    {
        $static = "\\pjdietz\\WellRESTed\\Routes\\StaticRoute";
        $prefix = "\\pjdietz\\WellRESTed\\Routes\\PrefixRoute";
        $template = "\\pjdietz\\WellRESTed\\Routes\\TemplateRoute";
        $regex = "\\pjdietz\\WellRESTed\\Routes\\RegexRoute";

        return [
            ["/cats/", $static],
            ["/cats/*", $prefix],
            ["/cats/{catId}", $template],
            ["~/cat/[0-9]+~", $regex]
        ];

    }
}
