<?php

namespace pjdietz\WellRESTed\Test;

use pjdietz\WellRESTed\Interfaces\HandlerInterface;
use pjdietz\WellRESTed\Response;
use pjdietz\WellRESTed\Routes\PrefixRoute;

class PrefixRouteTest extends \PHPUnit_Framework_TestCase
{
    public function testMatchSinglePathExactly()
    {
        $path = "/";

        $mockRequest = $this->getMock('\pjdietz\WellRESTed\Interfaces\RequestInterface');
        $mockRequest->expects($this->any())
            ->method('getPath')
            ->will($this->returnValue($path));

        $route = new PrefixRoute($path, __NAMESPACE__ . '\PrefixRouteTestHandler');
        $resp = $route->getResponse($mockRequest);
        $this->assertNotNull($resp);
    }

    public function testMatchSinglePathWithPrefix()
    {
        $mockRequest = $this->getMock('\pjdietz\WellRESTed\Interfaces\RequestInterface');
        $mockRequest->expects($this->any())
            ->method('getPath')
            ->will($this->returnValue("/cats/"));

        $route = new PrefixRoute("/", __NAMESPACE__ . '\PrefixRouteTestHandler');
        $resp = $route->getResponse($mockRequest);
        $this->assertNotNull($resp);
    }

    public function testMatchPathInList()
    {
        $paths = array("/cats/", "/dogs/");

        $mockRequest = $this->getMock('\pjdietz\WellRESTed\Interfaces\RequestInterface');
        $mockRequest->expects($this->any())
            ->method('getPath')
            ->will($this->returnValue("/cats/"));

        $route = new PrefixRoute($paths, __NAMESPACE__ . '\StaticRouteTestHandler');
        $resp = $route->getResponse($mockRequest);
        $this->assertEquals(200, $resp->getStatusCode());
    }

    public function testFailToMatchPath()
    {
        $path = "/cat/";

        $mockRequest = $this->getMock('\pjdietz\WellRESTed\Interfaces\RequestInterface');
        $mockRequest->expects($this->any())
            ->method('getPath')
            ->will($this->returnValue("/not-this-path/"));

        $route = new PrefixRoute($path, 'NoClass');
        $resp = $route->getResponse($mockRequest);
        $this->assertNull($resp);
    }

    /**
     * @dataProvider invalidPathsProvider
     * @expectedException  \InvalidArgumentException
     */
    public function testFailOnInvalidPath($path)
    {
        new PrefixRoute($path, 'NoClass');
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
class PrefixRouteTestHandler implements HandlerInterface
{
    public function getResponse(\pjdietz\WellRESTed\Interfaces\RequestInterface $request, array $args = null)
    {
        $resp = new Response();
        $resp->setStatusCode(200);
        return $resp;
    }
}
