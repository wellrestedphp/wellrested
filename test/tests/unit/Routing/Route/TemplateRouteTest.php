<?php

namespace WellRESTed\Test\Unit\Routing\Route;

use Prophecy\Argument;
use WellRESTed\Routing\Route\RouteInterface;
use WellRESTed\Routing\Route\TemplateRoute;

/**
 * @covers WellRESTed\Routing\Route\TemplateRoute
 * @uses WellRESTed\Routing\Route\RegexRoute
 * @uses WellRESTed\Routing\Route\Route
 */
class TemplateRouteTest extends \PHPUnit_Framework_TestCase
{
    private $methodMap;

    public function setUp()
    {
        $this->methodMap = $this->prophesize('WellRESTed\Routing\MethodMapInterface');
    }

    /**
     * @coversNothing
     */
    public function testReturnsPatternType()
    {
        $route = new TemplateRoute("/", $this->methodMap->reveal());
        $this->assertSame(RouteInterface::TYPE_PATTERN, $route->getType());
    }

    /**
     * @dataProvider matchingTemplateProvider
     */
    public function testMatchesTemplate($template, $requestTarget)
    {
        $route = new TemplateRoute($template, $this->methodMap->reveal());
        $this->assertTrue($route->matchesRequestTarget($requestTarget));
    }

    /**
     * @dataProvider matchingTemplateProvider
     */
    public function testProvidesCapturesAsRequestAttributes($template, $path, $expectedCaptures)
    {
        $request = $this->prophesize('Psr\Http\Message\ServerRequestInterface');
        $request->withAttribute(Argument::cetera())->willReturn($request->reveal());
        $response = $this->prophesize('Psr\Http\Message\ResponseInterface');
        $next = function ($request, $response) {
            return $response;
        };

        $route = new TemplateRoute($template, $this->methodMap->reveal());
        $route->matchesRequestTarget($path);
        $route->dispatch($request->reveal(), $response->reveal(), $next);

        $request->withAttribute("path", Argument::that(function ($path) use ($expectedCaptures) {
            return array_intersect_assoc($path, $expectedCaptures) == $expectedCaptures;
        }))->shouldHaveBeenCalled();

        //$request->withAttribute("path", $expectedCaptures)->shouldHaveBeenCalled();
    }

    public function matchingTemplateProvider()
    {
        return [
            ["/cat/{id}", "/cat/12", ["id" => "12"]],
            ["/unreserved/{id}", "/unreserved/az0-._~", ["id" => "az0-._~"]],
            ["/cat/{catId}/{dogId}",
                "/cat/molly/bear",
                [
                    "catId" => "molly",
                    "dogId" => "bear"
                ]
            ],
            [
                "/cat/{catId}/{dogId}",
                "/cat/molly/bear",
                [
                    "catId" => "molly",
                    "dogId" => "bear"
                ]
            ],
            ["/cat/{id}/*", "/cat/12/molly", ["id" => "12"]],
            [
                "/cat/{id}-{width}x{height}.jpg",
                "/cat/17-200x100.jpg",
                [
                    "id" => "17",
                    "width" => "200",
                    "height" => "100"
                ]
            ]
        ];
    }

    /**
     * @dataProvider allowedVariableNamesProvider
     */
    public function testMatchesAllowedVariablesNames($template, $path, $expectedCaptures)
    {
        $request = $this->prophesize('Psr\Http\Message\ServerRequestInterface');
        $request->withAttribute(Argument::cetera())->willReturn($request->reveal());
        $response = $this->prophesize('Psr\Http\Message\ResponseInterface');
        $next = function ($request, $response) {
            return $response;
        };
        $route = new TemplateRoute($template, $this->methodMap->reveal());
        $route->matchesRequestTarget($path);
        $route->dispatch($request->reveal(), $response->reveal(), $next);

        $request->withAttribute("path", Argument::that(function ($path) use ($expectedCaptures) {
            return array_intersect_assoc($path, $expectedCaptures) == $expectedCaptures;
        }))->shouldHaveBeenCalled();
    }

    public function allowedVariableNamesProvider()
    {
        return [
            ["/{n}", "/lower", ["n" => "lower"]],
            ["/{N}", "/UPPER", ["N" => "UPPER"]],
            ["/{var1024}", "/digits", ["var1024" => "digits"]],
            ["/{variable_name}", "/underscore", ["variable_name" => "underscore"]],
        ];
    }

    /**
     * @dataProvider illegalVariableNamesProvider
     */
    public function testFailsToMatchIllegalVariablesNames($template, $path)
    {
        $route = new TemplateRoute($template, $this->methodMap->reveal());
        $this->assertFalse($route->matchesRequestTarget($path));
    }

    public function illegalVariableNamesProvider()
    {
        return [
            ["/{not-legal}", "/hyphen"],
            ["/{1digitfirst}", "/digitfirst"],
            ["/{%2f}", "/percent-encoded"],
            ["/{}", "/empty"],
            ["/{{nested}}", "/nested"]
        ];
    }

    /**
     * @dataProvider nonmatchingTemplateProvider
     */
    public function testFailsToMatchNonmatchingTemplate($template, $path)
    {
        $route = new TemplateRoute($template, $this->methodMap->reveal());
        $this->assertFalse($route->matchesRequestTarget($path));
    }

    public function nonmatchingTemplateProvider()
    {
        return [
            ["/cat/{id}", "/cat/molly/the/cat"],
            ["/cat/{catId}/{dogId}", "/dog/12/13"]
        ];
    }
}
