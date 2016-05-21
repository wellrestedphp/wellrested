<?php

namespace WellRESTed\Test\Unit\Routing\Route;

use Prophecy\Argument;
use WellRESTed\Routing\Route\RegexRoute;
use WellRESTed\Routing\Route\RouteInterface;

/**
 * @covers WellRESTed\Routing\Route\RegexRoute
 * @group route
 * @group routing
 */
class RegexRouteTest extends \PHPUnit_Framework_TestCase
{
    private $methodMap;

    public function setUp()
    {
        $this->methodMap = $this->prophesize('WellRESTed\Routing\MethodMapInterface');
    }

    public function testReturnsPatternType()
    {
        $route = new RegexRoute("/", $this->methodMap->reveal());
        $this->assertSame(RouteInterface::TYPE_PATTERN, $route->getType());
    }

    /** @dataProvider matchingRouteProvider */
    public function testMatchesTarget($pattern, $path)
    {
        $route = new RegexRoute($pattern, $this->methodMap->reveal());
        $this->assertTrue($route->matchesRequestTarget($path));
    }

    /** @dataProvider matchingRouteProvider */
    public function testMatchesTargetByRegex($pattern, $target)
    {
        $route = new RegexRoute($pattern, $this->methodMap->reveal());
        $this->assertTrue($route->matchesRequestTarget($target));
    }

    /** @dataProvider matchingRouteProvider */
    public function testExtractsPathVariablesByRegex($pattern, $target, $expectedCaptures)
    {
        $route = new RegexRoute($pattern, $this->methodMap->reveal());
        $route->matchesRequestTarget($target);
        $this->assertEquals($expectedCaptures, $route->getPathVariables());
    }

    public function matchingRouteProvider()
    {
        return [
            ["~/cat/[0-9]+~", "/cat/2", [0 => "/cat/2"]],
            ["#/dog/.*#", "/dog/his-name-is-bear", [0 => "/dog/his-name-is-bear"]],
            ["~/cat/([0-9]+)~", "/cat/2", [
                0 => "/cat/2",
                1 => "2"
            ]],
            ["~/dog/(?<id>[0-9+])~", "/dog/2", [
                0 => "/dog/2",
                1 => "2",
                "id" => "2"
            ]]
        ];
    }

    /** @dataProvider mismatchingRouteProvider */
    public function testDoesNotMatchNonmatchingTarget($pattern, $path)
    {
        $route = new RegexRoute($pattern, $this->methodMap->reveal());
        $this->assertFalse($route->matchesRequestTarget($path));
    }

    public function mismatchingRouteProvider()
    {
        return [
            ["~/cat/[0-9]+~", "/cat/molly"],
            ["~/cat/[0-9]+~", "/dog/bear"],
            ["#/dog/.*#", "/dog"]
        ];
    }

    /**
     * @dataProvider invalidRouteProvider
     * @expectedException \RuntimeException
     */
    public function testThrowsExceptionOnInvalidPattern($pattern)
    {
        $route = new RegexRoute($pattern, $this->methodMap->reveal());
        \PHPUnit_Framework_Error_Warning::$enabled = false;
        \PHPUnit_Framework_Error_Notice::$enabled = false;
        $level = error_reporting();
        error_reporting($level & ~E_WARNING);
        $route->matchesRequestTarget("/");
        error_reporting($level);
        \PHPUnit_Framework_Error_Warning::$enabled = true;
        \PHPUnit_Framework_Error_Notice::$enabled = true;
    }

    public function invalidRouteProvider()
    {
        return [
            ["~/unterminated"],
            ["/nope"]
        ];
    }
}
