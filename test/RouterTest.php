<?php

namespace pjdietz\WellRESTed\Test;

use pjdietz\WellRESTed\Interfaces\HandlerInterface;
use pjdietz\WellRESTed\Interfaces\RequestInterface;
use pjdietz\WellRESTed\Response;
use pjdietz\WellRESTed\Router;
use pjdietz\WellRESTed\Routes\StaticRoute;

class RouterTest extends \PHPUnit_Framework_TestCase
{
    public function testAddRoute()
    {
        $path = "/";

        $mockRequest = $this->getMock('\pjdietz\WellRESTed\Interfaces\RequestInterface');
        $mockRequest->expects($this->any())
            ->method('getPath')
            ->will($this->returnValue($path));

        $route = new StaticRoute($path, __NAMESPACE__ . '\\RouterTestHandler');
        $router = new Router();
        $router->addRoute($route);
        $resp = $router->getResponse($mockRequest);
        $this->assertNotNull($resp);
    }

    public function testAddRoutes()
    {
        $path = "/";

        $mockRequest = $this->getMock('\pjdietz\WellRESTed\Interfaces\RequestInterface');
        $mockRequest->expects($this->any())
            ->method('getPath')
            ->will($this->returnValue($path));

        $routes = array();
        $routes[] = new StaticRoute("/", __NAMESPACE__ . '\\RouterTestHandler');
        $routes[] = new StaticRoute("/another/", __NAMESPACE__ . '\\RouterTestHandler');

        $router = new Router();
        $router->addRoutes($routes);
        $resp = $router->getResponse($mockRequest);
        $this->assertEquals(200, $resp->getStatusCode());
    }

    public function testGetNoRouteResponse()
    {
        $mockRequest = $this->getMock('\pjdietz\WellRESTed\Interfaces\RequestInterface');
        $mockRequest->expects($this->any())
            ->method('getPath')
            ->will($this->returnValue("/dog/"));

        $route = new StaticRoute("/cat/", __NAMESPACE__ . '\\RouterTestHandler');
        $router = new Router();
        $router->addRoute($route);
        $resp = $router->getResponse($mockRequest);
        $this->assertEquals(404, $resp->getStatusCode());
    }

    public function testStaticRequest()
    {
        $path = "/";
        $original = $_SERVER;
        $_SERVER["REQUEST_URI"] = $path;
        $_SERVER["HTTP_HOST"] = "localhost";

        $route = new StaticRoute($path, __NAMESPACE__ . '\\RouterTestHandler');
        $router = new Router();
        $router->addRoute($route);
        ob_start();
        @$router->respond();
        ob_end_clean();

        $_SERVER = $original;
    }
}

/**
 * Mini Handler class that allways returns a 200 status code Response.
 */
class RouterTestHandler implements HandlerInterface
{
    public function getResponse(RequestInterface $request, array $args = null)
    {
        $resp = new Response();
        $resp->setStatusCode(200);
        return $resp;
    }
}
