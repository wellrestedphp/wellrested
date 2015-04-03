<?php

namespace WellRESTed\Test\Unit\Routing\Route;

use Prophecy\Argument;
use WellRESTed\Routing\Route\PrefixRoute;

/**
 * @covers WellRESTed\Routing\Route\PrefixRoute
 * @uses WellRESTed\Routing\Route\Route
 */
class PrefixRouteTest extends \PHPUnit_Framework_TestCase
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

    public function testMatchesPrefix()
    {
        $route = new PrefixRoute("/cats/", $this->middleware->reveal());
        $this->assertTrue($route->matchesRequestTarget("/cats/molly"));
    }

    public function testFailsToMatchWrongPath()
    {
        $route = new PrefixRoute("/dogs/", $this->middleware->reveal());
        $this->assertFalse($route->matchesRequestTarget("/cats/"));
    }

    public function testReturnsPrefix()
    {
        $route = new PrefixRoute("/cats/", $this->middleware->reveal());
        $this->assertEquals("/cats/", $route->getPrefix());
    }
}
