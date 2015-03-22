<?php

namespace pjdietz\WellRESTed\Test;

use pjdietz\WellRESTed\Routes\StaticRoute;
use Prophecy\Argument;

/**
 * @covers pjdietz\WellRESTed\Routes\StaticRoute
 */
class StaticRouteTest extends \PHPUnit_Framework_TestCase
{
    private $handler;
    private $request;
    private $response;

    public function testMatchesSinglePath()
    {
        $this->request->getPath()->willReturn("/cats/");
        $route = new StaticRoute("/cats/", $this->handler->reveal());
        $resp = $route->getResponse($this->request->reveal());
        $this->assertNotNull($resp);
    }

    public function testMatchesPathInList()
    {
        $this->request->getPath()->willReturn("/cats/");
        $route = new StaticRoute(array("/cats/", "/dogs/"), $this->handler->reveal());
        $resp = $route->getResponse($this->request->reveal());
        $this->assertNotNull($resp);
    }

    public function testFailsToMatchPath()
    {
        $this->request->getPath()->willReturn("/dogs/");
        $route = new StaticRoute("/cats/", $this->handler->reveal());
        $resp = $route->getResponse($this->request->reveal());
        $this->assertNull($resp);
    }

    /**
     * @dataProvider invalidPathsProvider
     * @expectedException \InvalidArgumentException
     */
    public function testThrowsExceptionOnInvalidPath($path)
    {
        new StaticRoute($path, "\\NoClass");
    }

    public function invalidPathsProvider()
    {
        return array(
            array(false),
            array(17),
            array(null)
        );
    }

    public function testReturnsPaths()
    {
        $paths = array("/cats/", "/dogs/");
        $route = new StaticRoute($paths, $this->handler->reveal());
        $this->assertEquals($paths, $route->getPaths());
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

        $route = new StaticRoute("/", $callable);
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
