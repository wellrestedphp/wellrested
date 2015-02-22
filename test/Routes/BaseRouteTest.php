<?php

namespace pjdietz\WellRESTed\Test;

use pjdietz\WellRESTed\Routes\StaticRoute;
use Prophecy\Argument;

/**
 * @covers pjdietz\WellRESTed\Routes\BaseRoute
 */
class BaseRouteTest extends \PHPUnit_Framework_TestCase
{
    public function testDispatchesHandlerTarget()
    {
        $request = $this->prophesize("\\pjdietz\\WellRESTed\\Interfaces\\RequestInterface");
        $request->getPath()->willReturn("/");

        $response = $this->prophesize("\\pjdietz\\WellRESTed\\Interfaces\\ResponseInterface");

        $handler = $this->prophesize("\\pjdietz\\WellRESTed\\Interfaces\\HandlerInterface");
        $handler->getResponse(Argument::cetera())->willReturn($response->reveal());

        $route = new StaticRoute("/", $handler->reveal());
        $result = $route->getResponse($request->reveal());

        $this->assertSame($response->reveal(), $result);
        $handler->getResponse(Argument::cetera())->shouldHaveBeenCalled();
    }

    public function testDispatchesResponseTarget()
    {
        $request = $this->prophesize("\\pjdietz\\WellRESTed\\Interfaces\\RequestInterface");
        $request->getPath()->willReturn("/");

        $response = $this->prophesize("\\pjdietz\\WellRESTed\\Interfaces\\ResponseInterface");

        $route = new StaticRoute("/", $response->reveal());
        $result = $route->getResponse($request->reveal());

        $this->assertSame($response->reveal(), $result);
    }

    public function testDispatchesNullTarget()
    {
        $request = $this->prophesize("\\pjdietz\\WellRESTed\\Interfaces\\RequestInterface");
        $request->getPath()->willReturn("/");

        $route = new StaticRoute("/", function () { return null; });
        $result = $route->getResponse($request->reveal());

        $this->assertNull($result);
    }

    public function testPropagatesArgumentsToCallable()
    {
        $callableRequest = null;
        $callableArgs = null;
        $callable = function ($request, $args) use (&$callableRequest, &$callableArgs) {
            $callableRequest = $request;
            $callableArgs = $args;
        };

        $request = $this->prophesize("\\pjdietz\\WellRESTed\\Interfaces\\RequestInterface");
        $request->getPath()->willReturn("/");

        $args = ["cat" => "Molly"];

        $route = new StaticRoute("/", $callable);
        $route->getResponse($request->reveal(), $args);

        $this->assertSame($request->reveal(), $callableRequest);
        $this->assertSame($args, $callableArgs);
    }
}
