<?php

namespace WellRESTed\Test\Unit\Routing\Route;

use Prophecy\Argument;
use WellRESTed\Routing\Route\TemplateRoute;

/**
 * @covers WellRESTed\Routing\Route\TemplateRoute
 * @uses WellRESTed\Routing\Route\RegexRoute
 * @uses WellRESTed\Routing\Route\Route
 */
class TemplateRouteTest extends \PHPUnit_Framework_TestCase
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
     * @dataProvider matchingTemplateProvider
     */
    public function testMatchesTemplate($template, $vars, $path)
    {
        $route = new TemplateRoute($template, $this->middleware->reveal(), $vars);
        $this->assertTrue($route->matchesRequestTarget($path));
    }

    /**
     * @dataProvider matchingTemplateProvider
     */
    public function testExtractsCaptures($template, $vars, $path, $expectedCaptures)
    {
        $route = new TemplateRoute($template, $this->middleware->reveal(), $vars);
        $route->matchesRequestTarget($path, $captures);
        $this->assertEquals(0, count(array_diff_assoc($expectedCaptures, $captures)));
    }

    public function matchingTemplateProvider()
    {
        return [
            ["/cat/{id}", TemplateRoute::RE_NUM, "/cat/12", ["id" => "12"]],
            ["/cat/{catId}/{dogId}",
                TemplateRoute::RE_SLUG,
                "/cat/molly/bear",
                [
                    "catId" => "molly",
                    "dogId" => "bear"
                ]
            ],
            [
                "/cat/{catId}/{dogId}",
                [
                    "catId" => TemplateRoute::RE_SLUG,
                    "dogId" => TemplateRoute::RE_SLUG
                ],
                "/cat/molly/bear",
                [
                    "catId" => "molly",
                    "dogId" => "bear"
                ]
            ],
            ["/cat/{id}/*", null, "/cat/12/molly", ["id" => "12"]],
            [
                "/cat/{id}-{width}x{height}.jpg",
                TemplateRoute::RE_NUM,
                "/cat/17-200x100.jpg",
                [
                    "id" => "17",
                    "width" => "200",
                    "height" => "100"
                ]
            ],
            ["/cat/{path}", ".*", "/cat/this/section/has/slashes", ["path" => "this/section/has/slashes"]]
        ];
    }

    /**
     * @dataProvider allowedVariableNamesProvider
     */
    public function testMatchesAllowedVariablesNames($template, $path, $expectedCaptures)
    {
        $route = new TemplateRoute($template, $this->middleware->reveal());
        $route->matchesRequestTarget($path, $captures);
        $this->assertEquals(0, count(array_diff_assoc($expectedCaptures, $captures)));
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
        $route = new TemplateRoute($template, $this->middleware->reveal());
        $this->assertFalse($route->matchesRequestTarget($path, $captures));
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
    public function testFailsToMatchNonmatchingTemplate($template, $vars, $path)
    {
        $route = new TemplateRoute($template, $this->middleware->reveal(), $vars);
        $this->assertFalse($route->matchesRequestTarget($path, $captures));
    }

    public function nonmatchingTemplateProvider()
    {
        return [
            ["/cat/{id}", TemplateRoute::RE_NUM, "/cat/molly"],
            ["/cat/{catId}/{dogId}", TemplateRoute::RE_ALPHA, "/cat/12/13"],
            [
                "/cat/{catId}/{dogId}",
                [
                    "*" => TemplateRoute::RE_NUM,
                    "catId" => TemplateRoute::RE_ALPHA,
                    "dogId" => TemplateRoute::RE_ALPHA
                ],
                "/cat/12/13"
            ]
        ];
    }
}
