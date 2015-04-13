<?php

namespace WellRESTed\Test\Unit\Routing\Route;

use Prophecy\Argument;
use WellRESTed\Routing\Route\Route;
use WellRESTed\Routing\Route\StaticRoute;

/**
 * @uses WellRESTed\Routing\Route\Route
 * @uses WellRESTed\Routing\Route\StaticRoute
 */
class RouteTest extends \PHPUnit_Framework_TestCase
{
    private $request;
    private $response;

    public function setUp()
    {
        $this->request = $this->prophesize("\\Psr\\Http\\Message\\ServerRequestInterface");
        $this->response = $this->prophesize("\\Psr\\Http\\Message\\ResponseInterface");
        $this->response->withStatus(Argument::any())->willReturn($this->response->reveal());
    }

    public function testDispatchesMiddleware()
    {
        $middleware = function ($request, &$response) {
            $response = $response->withStatus(200);
        };
        $route = new StaticRoute("/", $middleware);
        $request = $this->request->reveal();
        $response = $this->response->reveal();
        $route->dispatch($request, $response);
        $this->response->withStatus(200)->shouldHaveBeenCalled();
    }
}
