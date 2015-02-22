<?php

namespace pjdietz\WellRESTed\Test;

use pjdietz\WellRESTed\Routes\PrefixRoute;
use Prophecy\Argument;

/**
 * @covers pjdietz\WellRESTed\Routes\PrefixRoute
 */
class PrefixRouteTest extends \PHPUnit_Framework_TestCase
{
    private $handler;
    private $request;
    private $response;

    public function testMatchesSinglePathExactly()
    {
        $this->request->getPath()->willReturn("/cats/");
        $route = new PrefixRoute("/cats/", $this->handler->reveal());
        $resp = $route->getResponse($this->request->reveal());
        $this->assertNotNull($resp);
    }

    public function testMatchesSinglePathWithPrefix()
    {
        $this->request->getPath()->willReturn("/cats/molly");
        $route = new PrefixRoute("/cats/", $this->handler->reveal());
        $resp = $route->getResponse($this->request->reveal());
        $this->assertNotNull($resp);
    }

    public function testMatchesPathInList()
    {
        $this->request->getPath()->willReturn("/cats/molly");
        $route = new PrefixRoute(array("/cats/", "/dogs/"), $this->handler->reveal());
        $resp = $route->getResponse($this->request->reveal());
        $this->assertNotNull($resp);
    }

    public function testFailsToMatchPath()
    {
        $this->request->getPath()->willReturn("/dogs/");
        $route = new PrefixRoute("/cats/", $this->handler->reveal());
        $resp = $route->getResponse($this->request->reveal());
        $this->assertNull($resp);
    }

    /**
     * @dataProvider invalidPathsProvider
     * @expectedException  \InvalidArgumentException
     */
    public function testThrowsExceptionOnInvalidPath($path)
    {
        new PrefixRoute($path, "\\NoClass");
    }

    public function invalidPathsProvider()
    {
        return array(
            array(false),
            array(17),
            array(null)
        );
    }

    public function testReturnsPrefixes()
    {
        $paths = array("/cats/", "/dogs/");
        $route = new PrefixRoute($paths, $this->handler->reveal());
        $this->assertEquals($paths, $route->getPrefixes());
    }

    public function testPropagatesArgumentsToCallable()
    {
        $callableRequest = null;
        $callableArgs = null;
        $callable = function ($request, $args) use (&$callableRequest, &$callableArgs) {
            $callableRequest = $request;
            $callableArgs = $args;
        };

        $this->request->getPath()->willReturn("/");
        $args = ["cat" => "Molly"];

        $route = new PrefixRoute("/", $callable);
        $route->getResponse($this->request->reveal(), $args);

        $this->assertSame($this->request->reveal(), $callableRequest);
        $this->assertSame($args, $callableArgs);
    }

    public function setUp()
    {
        $this->request = $this->prophesize("\\pjdietz\\WellRESTed\\Interfaces\\RequestInterface");
        $this->response = $this->prophesize("\\pjdietz\\WellRESTed\\Interfaces\\ResponseInterface");
        $this->handler = $this->prophesize("\\pjdietz\\WellRESTed\\Interfaces\\HandlerInterface");
        $this->handler->getResponse(Argument::cetera())->willReturn($this->response->reveal());
    }
}
