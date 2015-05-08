<?php

namespace WellRESTed\Test\Unit\Routing\Route;

use Prophecy\Argument;
use WellRESTed\Routing\Route\RegexRoute;
use WellRESTed\Routing\Route\RouteInterface;

/**
 * @coversDefaultClass WellRESTed\Routing\Route\RegexRoute
 * @uses WellRESTed\Routing\Route\RegexRoute
 * @uses WellRESTed\Routing\Route\Route
 */
class RegexRouteTest extends \PHPUnit_Framework_TestCase
{
    private $methodMap;

    public function setUp()
    {
        $this->methodMap = $this->prophesize('WellRESTed\Routing\MethodMapInterface');
    }

    /**
     * @covers ::getType
     */
    public function testReturnsPatternType()
    {
        $route = new RegexRoute("/", $this->methodMap->reveal());
        $this->assertSame(RouteInterface::TYPE_PATTERN, $route->getType());
    }

    /**
     * @covers ::matchesRequestTarget
     * @dataProvider matchingRouteProvider
     */
    public function testMatchesTarget($pattern, $path)
    {
        $route = new RegexRoute($pattern, $this->methodMap->reveal());
        $this->assertTrue($route->matchesRequestTarget($path));
    }

    /**
     * @dataProvider matchingRouteProvider
     */
    public function testProvidesCapturesAsRequestAttributes($pattern, $path, $expectedCaptures)
    {
        $request = $this->prophesize('Psr\Http\Message\ServerRequestInterface');
        $request->withAttribute(Argument::cetera())->willReturn($request->reveal());
        $response = $this->prophesize('Psr\Http\Message\ResponseInterface');
        $responseReveal = $response->reveal();

        $route = new RegexRoute($pattern, $this->methodMap->reveal());
        $route->matchesRequestTarget($path);
        $route->dispatch($request->reveal(), $responseReveal);

        $request->withAttribute("path", $expectedCaptures)->shouldHaveBeenCalled();
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
     * @expectedException  \RuntimeException
     */
    public function testThrowsExceptionOnInvalidPattern($pattern)
    {
        $route = new RegexRoute($pattern, $this->methodMap->reveal());
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
