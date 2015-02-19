<?php

namespace pjdietz\WellRESTed\Test;

use pjdietz\WellRESTed\Interfaces\HandlerInterface;
use pjdietz\WellRESTed\Interfaces\RequestInterface;
use pjdietz\WellRESTed\Routes\StaticRoute;

/**
 * @covers pjdietz\WellRESTed\Routes\BaseRoute
 */
class BaseRouteTest extends \PHPUnit_Framework_TestCase
{
    private $path = "/";
    private $request;

    public function testDispatchesHandlerFromCallable()
    {
        $target = function () {
            $handler = $this->prophesize("\\pjdietz\\WellRESTed\\Interfaces\\HandlerInterface");
            return $handler->reveal();
        };

        $route = new StaticRoute($this->path, $target);
        $route->getResponse($this->request->reveal());
    }

    public function testDispatchesHandlerFromString()
    {
        $target = __NAMESPACE__ . "\\ValidHandler";

        $route = new StaticRoute($this->path, $target);
        $route->getResponse($this->request->reveal());
    }

    public function testDispatchesHandlerInstance()
    {
        $target = new ValidHandler();

        $route = new StaticRoute($this->path, $target);
        $route->getResponse($this->request->reveal());
    }

    /**
     * @expectedException  \UnexpectedValueException
     */
    public function testThrowsExceptionWhenHandlerDoesNotImplementInterface()
    {
        $target = "\\stdClass";

        $route = new StaticRoute($this->path, $target);
        $route->getResponse($this->request->reveal());
    }

    public function setUp()
    {
        $this->request = $this->prophesize("\\pjdietz\\WellRESTed\\Interfaces\\RequestInterface");
        $this->request->getPath()->willReturn($this->path);
    }
}

class ValidHandler implements HandlerInterface
{
    public function getResponse(RequestInterface $request, array $args = null)
    {
        return null;
    }
}
