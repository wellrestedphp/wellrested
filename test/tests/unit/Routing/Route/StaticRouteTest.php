<?php

namespace WellRESTed\Test\Unit\Routing\Route;

use Prophecy\Argument;
use WellRESTed\Routing\Route\StaticRoute;

/**
 * @covers WellRESTed\Routing\Route\StaticRoute
 * @uses WellRESTed\Routing\Route\Route
 */
class StaticRouteTest extends \PHPUnit_Framework_TestCase
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

    public function testMatchesPath()
    {
        $route = new StaticRoute("/cats/", $this->middleware->reveal());
        $this->assertTrue($route->matchesRequestTarget("/cats/"));
    }

    public function testFailsToMatchWrongPath()
    {
        $route = new StaticRoute("/dogs/", $this->middleware->reveal());
        $this->assertFalse($route->matchesRequestTarget("/cats/"));
    }

    public function testReturnsPaths()
    {
        $route = new StaticRoute("/cats/", $this->middleware->reveal());
        $this->assertEquals("/cats/", $route->getPath());
    }
}
