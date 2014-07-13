<?php

use pjdietz\WellRESTed\Interfaces\HandlerInterface;
use pjdietz\WellRESTed\Response;
use pjdietz\WellRESTed\Routes\StaticRoute;

class StaticRouteTest extends \PHPUnit_Framework_TestCase
{
    public function testSinglePathMatch()
    {
        $path = "/";

        $mockRequest = $this->getMock('\pjdietz\WellRESTed\Interfaces\RequestInterface');
        $mockRequest->expects($this->any())
            ->method('getPath')
            ->will($this->returnValue($path));

        $route = new StaticRoute($path, 'HandlerStub');
        $resp = $route->getResponse($mockRequest);
        $this->assertEquals(200, $resp->getStatusCode());
    }

    public function testSinglePathNoMatch()
    {
        $path = "/";

        $mockRequest = $this->getMock('\pjdietz\WellRESTed\Interfaces\RequestInterface');
        $mockRequest->expects($this->any())
            ->method('getPath')
            ->will($this->returnValue("/not-this-path/"));

        $route = new StaticRoute($path, 'HandlerStub');
        $resp = $route->getResponse($mockRequest);
        $this->assertNull($resp);
    }

    public function testListPathMatch()
    {
        $path = "/";
        $paths = array($path, "/cats/", "/dogs/");

        $mockRequest = $this->getMock('\pjdietz\WellRESTed\Interfaces\RequestInterface');
        $mockRequest->expects($this->any())
            ->method('getPath')
            ->will($this->returnValue($path));

        $route = new StaticRoute($paths, 'HandlerStub');
        $resp = $route->getResponse($mockRequest);
        $this->assertEquals(200, $resp->getStatusCode());
    }

    /**
     * @dataProvider invalidPathsProvider
     * @expectedException  \InvalidArgumentException
     */
    public function testInvalidPath($path)
    {
        $route = new StaticRoute($path, 'HandlerStub');
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
class HandlerStub implements HandlerInterface
{
    public function getResponse(\pjdietz\WellRESTed\Interfaces\RequestInterface $request, array $args = null)
    {
        $resp = new Response();
        $resp->setStatusCode(200);
        return $resp;
    }
}
