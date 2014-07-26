<?php

namespace pjdietz\WellRESTed\Test;

use pjdietz\WellRESTed\Interfaces\HandlerInterface;
use pjdietz\WellRESTed\Interfaces\RequestInterface;
use pjdietz\WellRESTed\Response;
use pjdietz\WellRESTed\Routes\RegexRoute;

class RegexRouteTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider matchingRouteProvider
     */
    public function testMatchPatternForRoute($pattern, $path)
    {
        $mockRequest = $this->getMock('\pjdietz\WellRESTed\Interfaces\RequestInterface');
        $mockRequest->expects($this->any())
            ->method('getPath')
            ->will($this->returnValue($path));

        $route = new RegexRoute($pattern, __NAMESPACE__ . '\RegexRouteTestHandler');
        $resp = $route->getResponse($mockRequest);
        $this->assertNotNull($resp);
    }

    public function matchingRouteProvider()
    {
        return [
            ["~/cat/[0-9]+~", "/cat/2"],
            ["#/dog/.*#", "/dog/his-name-is-bear"]
        ];
    }

    /**
     * @dataProvider mismatchingRouteProvider
     */
    public function testSkipMismatchingPattern($pattern, $path)
    {
        $mockRequest = $this->getMock('\pjdietz\WellRESTed\Interfaces\RequestInterface');
        $mockRequest->expects($this->any())
            ->method('getPath')
            ->will($this->returnValue($path));

        $route = new RegexRoute($pattern, 'NoClass');
        $resp = $route->getResponse($mockRequest);
        $this->assertNull($resp);
    }

    public function mismatchingRouteProvider()
    {
        return [
            ["~/cat/[0-9]+~", "/cat/molly"],
            ["~/cat/[0-9]+~", "/dog/bear"],
            ["#/dog/.*#", "/dog"]
       ];
    }

    /**
     * @dataProvider invalidRouteProvider
     * @expectedException  \pjdietz\WellRESTed\Exceptions\ParseException
     */
    public function testFailOnInvalidPattern($pattern)
    {
        $mockRequest = $this->getMock('\pjdietz\WellRESTed\Interfaces\RequestInterface');

        $route = new RegexRoute($pattern, 'NoClass');
        $resp = $route->getResponse($mockRequest);
        $this->assertNull($resp);
    }

    public function invalidRouteProvider()
    {
        return [
            ["~/unterminated"],
            ["/nope"]
        ];
    }

}

/**
 * Mini Handler class that allways returns a 200 status code Response.
 */
class RegexRouteTestHandler implements HandlerInterface
{
    public function getResponse(RequestInterface $request, array $args = null)
    {
        $resp = new Response();
        $resp->setStatusCode(200);
        return $resp;
    }
}
