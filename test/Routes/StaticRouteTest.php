<?php

namespace pjdietz\WellRESTed\Test;

use pjdietz\WellRESTed\Interfaces\HandlerInterface;
use pjdietz\WellRESTed\Response;
use pjdietz\WellRESTed\Routes\StaticRoute;

class StaticRouteTest extends \PHPUnit_Framework_TestCase
{
    public function testMatchSinglePath()
    {
        $path = "/";

        $mockRequest = $this->getMock('\pjdietz\WellRESTed\Interfaces\RequestInterface');
        $mockRequest->expects($this->any())
            ->method('getPath')
            ->will($this->returnValue($path));

        $route = new StaticRoute($path, __NAMESPACE__ . '\StaticRouteTestHandler');
        $resp = $route->getResponse($mockRequest);
        $this->assertEquals(200, $resp->getStatusCode());
    }

    public function testMatchPathInList()
    {
        $path = "/";
        $paths = array($path, "/cats/", "/dogs/");

        $mockRequest = $this->getMock('\pjdietz\WellRESTed\Interfaces\RequestInterface');
        $mockRequest->expects($this->any())
            ->method('getPath')
            ->will($this->returnValue($path));

        $route = new StaticRoute($paths, __NAMESPACE__ . '\StaticRouteTestHandler');
        $resp = $route->getResponse($mockRequest);
        $this->assertEquals(200, $resp->getStatusCode());
    }

    public function testFailToMatchPath()
    {
        $path = "/";

        $mockRequest = $this->getMock('\pjdietz\WellRESTed\Interfaces\RequestInterface');
        $mockRequest->expects($this->any())
            ->method('getPath')
            ->will($this->returnValue("/not-this-path/"));

        $route = new StaticRoute($path, 'NoClass');
        $resp = $route->getResponse($mockRequest);
        $this->assertNull($resp);
    }

    /**
     * @dataProvider invalidPathsProvider
     * @expectedException  \InvalidArgumentException
     */
    public function testFailOnInvalidPath($path)
    {
        new StaticRoute($path, 'NoClass');
    }

    public function invalidPathsProvider()
    {
        return array(
            array(false),
            array(17),
            array(null)
        );
    }

}

/**
 * Mini Handler class that allways returns a 200 status code Response.
 */
class StaticRouteTestHandler implements HandlerInterface
{
    public function getResponse(\pjdietz\WellRESTed\Interfaces\RequestInterface $request, array $args = null)
    {
        $resp = new Response();
        $resp->setStatusCode(200);
        return $resp;
    }
}
