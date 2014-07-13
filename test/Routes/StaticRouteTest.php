<?php

namespace pjdietz\WellRESTed\Test;

use pjdietz\WellRESTed\Routes\StaticRoute;

class StaticRouteTest extends \PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        include_once(__DIR__ . "/../src/MockHandler.php");
    }

    public function testSinglePathMatch()
    {
        $path = "/";

        $mockRequest = $this->getMock('\pjdietz\WellRESTed\Interfaces\RequestInterface');
        $mockRequest->expects($this->any())
            ->method('getPath')
            ->will($this->returnValue($path));

        $route = new StaticRoute($path, 'MockHandler');
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

        $route = new StaticRoute($paths, 'MockHandler');
        $resp = $route->getResponse($mockRequest);
        $this->assertEquals(200, $resp->getStatusCode());
    }

    /**
     * @dataProvider invalidPathsProvider
     * @expectedException  \InvalidArgumentException
     */
    public function testInvalidPath($path)
    {
        $route = new StaticRoute($path, 'MockHandler');
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
