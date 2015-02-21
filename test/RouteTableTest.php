<?php

namespace pjdietz\WellRESTed\Test;

use pjdietz\WellRESTed\RouteTable;
use Prophecy\Argument;

/**
 * @covers pjdietz\WellRESTed\RouteTable
 */
class RouteTableTest extends \PHPUnit_Framework_TestCase
{
    private $handler;
    private $request;
    private $response;
    private $route;

    public function setUp()
    {
        $this->request = $this->prophesize("\\pjdietz\\WellRESTed\\Interfaces\\RequestInterface");
        $this->response = $this->prophesize("\\pjdietz\\WellRESTed\\Interfaces\\ResponseInterface");
        $this->route = $this->prophesize("\\pjdietz\\WellRESTed\\Interfaces\\HandlerInterface");
        $this->handler = $this->prophesize("\\pjdietz\\WellRESTed\\Interfaces\\HandlerInterface");
    }

    public function testReturnsNullWhenNoRoutesMatch()
    {
        $table = new RouteTable();
        $response = $table->getResponse($this->request->reveal());
        $this->assertNull($response);
    }

    public function testMatchesStaticRoute()
    {
        $this->handler->getResponse(Argument::cetera())->willReturn($this->response->reveal());

        $this->route->willImplement("\\pjdietz\\WellRESTed\\Interfaces\\Routes\\StaticRouteInterface");
        $this->route->getPaths()->willReturn(["/cats/"]);
        $this->route->getHandler()->willReturn($this->handler->reveal());

        $this->request->getPath()->willReturn("/cats/");

        $table = new RouteTable();
        $table->addRoute($this->route->reveal());
        $table->getResponse($this->request->reveal());

        $this->route->getHandler()->shouldHaveBeenCalled();
    }

    public function testMatchesPrefixRoute()
    {
        $this->handler->getResponse(Argument::cetera())->willReturn($this->response->reveal());

        $this->route->willImplement("\\pjdietz\\WellRESTed\\Interfaces\\Routes\\PrefixRouteInterface");
        $this->route->getPrefixes()->willReturn(["/cats/"]);
        $this->route->getHandler()->willReturn($this->handler->reveal());

        $this->request->getPath()->willReturn("/cats/molly");

        $table = new RouteTable();
        $table->addRoute($this->route->reveal());
        $table->getResponse($this->request->reveal());

        $this->route->getHandler()->shouldHaveBeenCalled();
    }

    public function testMatchesBestPrefixRoute()
    {
        $this->handler->getResponse(Argument::cetera())->willReturn($this->response->reveal());

        $route1 = $this->prophesize("\\pjdietz\\WellRESTed\\Interfaces\\HandlerInterface");
        $route1->willImplement("\\pjdietz\\WellRESTed\\Interfaces\\Routes\\PrefixRouteInterface");
        $route1->getPrefixes()->willReturn(["/animals/"]);
        $route1->getHandler()->willReturn($this->handler->reveal());

        $route2 = $this->prophesize("\\pjdietz\\WellRESTed\\Interfaces\\HandlerInterface");
        $route2->willImplement("\\pjdietz\\WellRESTed\\Interfaces\\Routes\\PrefixRouteInterface");
        $route2->getPrefixes()->willReturn(["/animals/cats/"]);
        $route2->getHandler()->willReturn($this->handler->reveal());

        $this->request->getPath()->willReturn("/animals/cats/molly");

        $table = new RouteTable();
        $table->addRoute($route1->reveal());
        $table->addRoute($route2->reveal());
        $table->getResponse($this->request->reveal());

        $route1->getHandler()->shouldNotHaveBeenCalled();
        $route2->getHandler()->shouldHaveBeenCalled();
    }

    public function testMatchesStaticRouteBeforePrefixRoute()
    {
        $this->handler->getResponse(Argument::cetera())->willReturn($this->response->reveal());

        $route1 = $this->prophesize("\\pjdietz\\WellRESTed\\Interfaces\\HandlerInterface");
        $route1->willImplement("\\pjdietz\\WellRESTed\\Interfaces\\Routes\\PrefixRouteInterface");
        $route1->getPrefixes()->willReturn(["/animals/cats/"]);
        $route1->getHandler()->willReturn($this->handler->reveal());

        $route2 = $this->prophesize("\\pjdietz\\WellRESTed\\Interfaces\\HandlerInterface");
        $route2->willImplement("\\pjdietz\\WellRESTed\\Interfaces\\Routes\\StaticRouteInterface");
        $route2->getPaths()->willReturn(["/animals/cats/molly"]);
        $route2->getHandler()->willReturn($this->handler->reveal());

        $this->request->getPath()->willReturn("/animals/cats/molly");

        $table = new RouteTable();
        $table->addRoute($route1->reveal());
        $table->addRoute($route2->reveal());
        $table->getResponse($this->request->reveal());

        $route1->getHandler()->shouldNotHaveBeenCalled();
        $route2->getHandler()->shouldHaveBeenCalled();
    }

    public function testMatchesPrefixRouteBeforeHandlerRoute()
    {
        $this->handler->getResponse(Argument::cetera())->willReturn($this->response->reveal());

        $route1 = $this->prophesize("\\pjdietz\\WellRESTed\\Interfaces\\HandlerInterface");
        $route1->willImplement("\\pjdietz\\WellRESTed\\Interfaces\\Routes\\PrefixRouteInterface");
        $route1->getPrefixes()->willReturn(["/animals/cats/"]);
        $route1->getHandler()->willReturn($this->handler->reveal());

        $route2 = $this->prophesize("\\pjdietz\\WellRESTed\\Interfaces\\HandlerInterface");
        $route2->getResponse(Argument::cetera())->willReturn(null);

        $this->request->getPath()->willReturn("/animals/cats/molly");

        $table = new RouteTable();
        $table->addRoute($route1->reveal());
        $table->addRoute($route2->reveal());
        $table->getResponse($this->request->reveal());

        $route1->getHandler()->shouldHaveBeenCalled();
        $route2->getResponse(Argument::cetera())->shouldNotHaveBeenCalled();
    }

    public function testReturnsFirstNonNullResponse()
    {
        $route1 = $this->prophesize("\\pjdietz\\WellRESTed\\Interfaces\\HandlerInterface");
        $route1->getResponse(Argument::cetera())->willReturn(null);

        $route2 = $this->prophesize("\\pjdietz\\WellRESTed\\Interfaces\\HandlerInterface");
        $route2->getResponse(Argument::cetera())->willReturn($this->response->reveal());

        $route3 = $this->prophesize("\\pjdietz\\WellRESTed\\Interfaces\\HandlerInterface");
        $route3->getResponse(Argument::cetera())->willReturn(null);

        $this->request->getPath()->willReturn("/");

        $table = new RouteTable();
        $table->addRoutes(
            [
                $route1->reveal(),
                $route2->reveal(),
                $route3->reveal()
            ]
        );
        $response = $table->getResponse($this->request->reveal());

        $this->assertNotNull($response);
        $route1->getResponse(Argument::cetera())->shouldHaveBeenCalled();
        $route2->getResponse(Argument::cetera())->shouldHaveBeenCalled();
        $route3->getResponse(Argument::cetera())->shouldNotHaveBeenCalled();
    }
}
