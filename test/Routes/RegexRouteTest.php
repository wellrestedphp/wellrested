<?php

namespace pjdietz\WellRESTed\Test;

use pjdietz\WellRESTed\Routes\RegexRoute;

class RegexRouteTest extends \PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        include_once(__DIR__ . "/../src/MockHandler.php");
    }

    /**
     * @dataProvider matchingRouteProvider
     */
    public function testMatchingRoute($pattern, $path)
    {
        $mockRequest = $this->getMock('\pjdietz\WellRESTed\Interfaces\RequestInterface');
        $mockRequest->expects($this->any())
            ->method('getPath')
            ->will($this->returnValue($path));

        $route = new RegexRoute($pattern, 'MockHandler');
        $resp = $route->getResponse($mockRequest);
        $this->assertNotNull($resp);
    }

    public function matchingRouteProvider()
    {
        return array(
            array("~/cat/[0-9]+~", "/cat/2"),
            array("#/dog/.*#", "/dog/his-name-is-bear")
        );
    }

    /**
     * @dataProvider nonmatchingRouteProvider
     */
    public function testNonmatchingRoute($pattern, $path)
    {
        $mockRequest = $this->getMock('\pjdietz\WellRESTed\Interfaces\RequestInterface');
        $mockRequest->expects($this->any())
            ->method('getPath')
            ->will($this->returnValue($path));

        $route = new RegexRoute($pattern, 'MockHandler');
        $resp = $route->getResponse($mockRequest);
        $this->assertNull($resp);
    }

    public function nonmatchingRouteProvider()
    {
        return array(
            array("~/cat/[0-9]+~", "/cat/molly"),
            array("~/cat/[0-9]+~", "/dog/bear"),
            array("#/dog/.*#", "/dog")
        );
    }

    /**
     * @dataProvider invalidRouteProvider
     * @expectedException  \pjdietz\WellRESTed\Exceptions\ParseException
     */
    public function testInvalidRoute($pattern)
    {
        $mockRequest = $this->getMock('\pjdietz\WellRESTed\Interfaces\RequestInterface');

        $route = new RegexRoute($pattern, 'MockHandler');
        $resp = $route->getResponse($mockRequest);
        $this->assertNull($resp);
    }

    public function invalidRouteProvider()
    {
        return array(
            array("~/unterminated"),
            array("/nope")
        );
    }

}
