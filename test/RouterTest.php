<?php

namespace pjdietz\WellRESTed\Test;

use pjdietz\WellRESTed\Router;
use pjdietz\WellRESTed\Routes\StaticRoute;

class RouterTest extends \PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        include_once(__DIR__ . "/src/MockHandler.php");
    }

    public function testAddRoute()
    {
        $path = "/";

        $mockRequest = $this->getMock('\pjdietz\WellRESTed\Interfaces\RequestInterface');
        $mockRequest->expects($this->any())
            ->method('getPath')
            ->will($this->returnValue($path));

        $route = new StaticRoute($path, 'MockHandler');
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
        $routes[] = new StaticRoute("/not-this", 'MockHandler');
        $routes[] = new StaticRoute("/or-this", 'MockHandler');

        $router = new Router();
        $router->addRoutes($routes);
        $resp = $router->getResponse($mockRequest);
        $this->assertEquals(404, $resp->getStatusCode());

    }

    public function testStaticRequest()
    {
        $path = "/";
        $original = $_SERVER;
        $_SERVER["REQUEST_URI"] = $path;
        $_SERVER["HTTP_HOST"] = "localhost";

        $route = new StaticRoute($path, 'MockHandler');
        $router = new Router();
        $router->addRoute($route);
        ob_start();
        @$router->respond();
        ob_end_clean();

        $_SERVER = $original;
    }

}
