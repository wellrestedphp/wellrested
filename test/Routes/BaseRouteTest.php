<?php

namespace pjdietz\WellRESTed\Test;

use pjdietz\WellRESTed\Routes\StaticRoute;
use Prophecy\Argument;

/**
 * @covers pjdietz\WellRESTed\Routes\BaseRoute
 */
class BaseRouteTest extends \PHPUnit_Framework_TestCase
{
    public function testDispatchesHandlerInstance()
    {
        $request = $this->prophesize("\\pjdietz\\WellRESTed\\Interfaces\\RequestInterface");
        $request->getPath()->willReturn("/");

        $handler = $this->prophesize("\\pjdietz\\WellRESTed\\Interfaces\\HandlerInterface");
        $handler->getResponse(Argument::cetera())->willReturn(null);

        $route = new StaticRoute("/", $handler->reveal());
        $route->getResponse($request->reveal());
        $handler->getResponse(Argument::cetera())->shouldHaveBeenCalled();
    }
}
