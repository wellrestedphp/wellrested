<?php

namespace pjdietz\WellRESTed\Test;

use pjdietz\WellRESTed\Routes\RegexRoute;
use Prophecy\Argument;

/**
 * @covers pjdietz\WellRESTed\Routes\RegexRoute
 */
class RegexRouteTest extends \PHPUnit_Framework_TestCase
{
    private $handler;
    private $request;
    private $response;

    /**
     * @dataProvider matchingRouteProvider
     */
    public function testMatchesPattern($pattern, $path)
    {
        $this->request->getPath()->willReturn($path);

        $route = new RegexRoute($pattern, $this->handler->reveal());
        $resp = $route->getResponse($this->request->reveal());
        $this->assertNotNull($resp);
    }

    /**
     * @dataProvider matchingRouteProvider
     */
    public function testExtractsCaptures($pattern, $path, $captures)
    {
        $this->request->getPath()->willReturn($path);

        $route = new RegexRoute($pattern, $this->handler->reveal());
        $route->getResponse($this->request->reveal());
        $this->handler->getResponse(Argument::any(), Argument::that(
            function ($args) use ($captures) {
                return $args = $captures;
            }))->shouldHaveBeenCalled();
    }

    public function matchingRouteProvider()
    {
        return [
            ["~/cat/[0-9]+~", "/cat/2", [0 => "/cat/2"]],
            ["#/dog/.*#", "/dog/his-name-is-bear", [0 => "/dog/his-name-is-bear"]],
            ["~/cat/([0-9]+)~", "/cat/2", [
                0 => "/cat/2",
                1 => "2"
            ]],
            ["~/dog/(?<id>[0-9+])~", "/dog/2", [
                0 => "/dog/2",
                1 => "2",
                "id" => "2"
            ]]
        ];
    }

    /**
     * @dataProvider mismatchingRouteProvider
     */
    public function testFailsToMatchMismatchingPattern($pattern, $path)
    {
        $this->request->getPath()->willReturn($path);

        $route = new RegexRoute($pattern, $this->handler->reveal());
        $resp = $route->getResponse($this->request->reveal());
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
    public function testThrowsExceptionOnInvalidPattern($pattern)
    {
        $route = new RegexRoute($pattern, $this->handler->reveal());
        $route->getResponse($this->request->reveal());
    }

    public function invalidRouteProvider()
    {
        return [
            ["~/unterminated"],
            ["/nope"]
        ];
    }

    public function testPropagatesArgumentsToCallable()
    {
        $callableRequest = null;
        $callableArgs = null;
        $callable = function ($request, $args) use (&$callableRequest, &$callableArgs) {
            $callableRequest = $request;
            $callableArgs = $args;
        };

        $this->request->getPath()->willReturn("/dog/bear");
        $args = ["cat" => "Molly"];

        $route = new RegexRoute("~/dog/(?<dog>[a-z]+)~", $callable);
        $route->getResponse($this->request->reveal(), $args);

        $this->assertSame($this->request->reveal(), $callableRequest);
        $this->assertArraySubset($args, $callableArgs);
        $this->assertArraySubset(["dog" => "bear"], $callableArgs);
    }

    public function setUp()
    {
        $this->request = $this->prophesize("\\pjdietz\\WellRESTed\\Interfaces\\RequestInterface");
        $this->response = $this->prophesize("\\pjdietz\\WellRESTed\\Interfaces\\ResponseInterface");
        $this->handler = $this->prophesize("\\pjdietz\\WellRESTed\\Interfaces\\HandlerInterface");
        $this->handler->getResponse(Argument::cetera())->willReturn($this->response->reveal());
    }
}
