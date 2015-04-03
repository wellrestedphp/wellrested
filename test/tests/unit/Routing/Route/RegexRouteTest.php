<?php

namespace WellRESTed\Test\Unit\Routing\Route;

use Prophecy\Argument;
use WellRESTed\Routing\Route\RegexRoute;

/**
 * @covers WellRESTed\Routing\Route\RegexRoute
 * @uses WellRESTed\Routing\Route\Route
 */
class RegexRouteTest extends \PHPUnit_Framework_TestCase
{
    private $request;
    private $response;
    private $middleware;

    public function setUp()
    {
        $this->request = $this->prophesize("\\Psr\\Http\\Message\\ServerRequestInterface");
        $this->response = $this->prophesize("\\Psr\\Http\\Message\\ResponseInterface");
        $this->middleware = $this->prophesize("\\WellRESTed\\Routing\\MiddlewareInterface");
    }

    /**
     * @dataProvider matchingRouteProvider
     */
    public function testMatchesPattern($pattern, $path)
    {
        $route = new RegexRoute($pattern, $this->middleware->reveal());
        $this->assertTrue($route->matchesRequestTarget($path));
    }

    /**
     * @dataProvider matchingRouteProvider
     */
    public function testExtractsCaptures($pattern, $path, $expectedCaptures)
    {
        $route = new RegexRoute($pattern, $this->middleware->reveal());
        $route->matchesRequestTarget($path, $captures);
        $this->assertEquals($expectedCaptures, $captures);
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

    /**
     * @dataProvider mismatchingRouteProvider
     */
    public function testFailsToMatchMismatchingPattern($pattern, $path)
    {
        $route = new RegexRoute($pattern, $this->middleware->reveal());
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
     * @expectedException  \RuntimeException
     */
    public function testThrowsExceptionOnInvalidPattern($pattern)
    {
        $route = new RegexRoute($pattern, $this->middleware->reveal());
        $route->matchesRequestTarget("/");
    }

    public function invalidRouteProvider()
    {
        return [
            ["~/unterminated"],
            ["/nope"]
        ];
    }
}
