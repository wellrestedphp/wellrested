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
        $this->route->willImplement("\\pjdietz\\WellRESTed\\Interfaces\\Routes\\StaticRouteInterface");
        $this->route->getPaths()->willReturn(["/cats/"]);
        $this->route->getResponse(Argument::cetera())->willReturn($this->response->reveal());

        $this->request->getPath()->willReturn("/cats/");

        $table = new RouteTable();
        $table->addRoute($this->route->reveal());
        $table->getResponse($this->request->reveal());

        $this->route->getResponse(Argument::cetera())->shouldHaveBeenCalled();
    }

    public function testMatchesPrefixRoute()
    {
        $this->route->willImplement("\\pjdietz\\WellRESTed\\Interfaces\\Routes\\PrefixRouteInterface");
        $this->route->getPrefixes()->willReturn(["/cats/"]);
        $this->route->getResponse(Argument::cetera())->willReturn($this->response->reveal());

        $this->request->getPath()->willReturn("/cats/molly");

        $table = new RouteTable();
        $table->addRoute($this->route->reveal());
        $table->getResponse($this->request->reveal());

        $this->route->getResponse(Argument::cetera())->shouldHaveBeenCalled();
    }

    public function testMatchesBestPrefixRoute()
    {
        $route1 = $this->prophesize("\\pjdietz\\WellRESTed\\Interfaces\\HandlerInterface");
        $route1->willImplement("\\pjdietz\\WellRESTed\\Interfaces\\Routes\\PrefixRouteInterface");
        $route1->getPrefixes()->willReturn(["/animals/"]);
        $route1->getResponse(Argument::cetera())->willReturn($this->response->reveal());

        $route2 = $this->prophesize("\\pjdietz\\WellRESTed\\Interfaces\\HandlerInterface");
        $route2->willImplement("\\pjdietz\\WellRESTed\\Interfaces\\Routes\\PrefixRouteInterface");
        $route2->getPrefixes()->willReturn(["/animals/cats/"]);
        $route2->getResponse(Argument::cetera())->willReturn($this->response->reveal());

        $this->request->getPath()->willReturn("/animals/cats/molly");

        $table = new RouteTable();
        $table->addRoute($route1->reveal());
        $table->addRoute($route2->reveal());
        $table->getResponse($this->request->reveal());

        $route1->getResponse(Argument::cetera())->shouldNotHaveBeenCalled();
        $route2->getResponse(Argument::cetera())->shouldHaveBeenCalled();
    }

    public function testMatchesStaticRouteBeforePrefixRoute()
    {
        $route1 = $this->prophesize("\\pjdietz\\WellRESTed\\Interfaces\\HandlerInterface");
        $route1->willImplement("\\pjdietz\\WellRESTed\\Interfaces\\Routes\\PrefixRouteInterface");
        $route1->getPrefixes()->willReturn(["/animals/cats/"]);
        $route1->getResponse(Argument::cetera())->willReturn($this->response->reveal());

        $route2 = $this->prophesize("\\pjdietz\\WellRESTed\\Interfaces\\HandlerInterface");
        $route2->willImplement("\\pjdietz\\WellRESTed\\Interfaces\\Routes\\StaticRouteInterface");
        $route2->getPaths()->willReturn(["/animals/cats/molly"]);
        $route2->getResponse(Argument::cetera())->willReturn($this->response->reveal());

        $this->request->getPath()->willReturn("/animals/cats/molly");

        $table = new RouteTable();
        $table->addRoute($route1->reveal());
        $table->addRoute($route2->reveal());
        $table->getResponse($this->request->reveal());

        $route1->getResponse(Argument::cetera())->shouldNotHaveBeenCalled();
        $route2->getResponse(Argument::cetera())->shouldHaveBeenCalled();
    }

    public function testMatchesPrefixRouteBeforeHandlerRoute()
    {
        $route1 = $this->prophesize("\\pjdietz\\WellRESTed\\Interfaces\\HandlerInterface");
        $route1->willImplement("\\pjdietz\\WellRESTed\\Interfaces\\Routes\\PrefixRouteInterface");
        $route1->getPrefixes()->willReturn(["/animals/cats/"]);
        $route1->getResponse(Argument::cetera())->willReturn($this->response->reveal());

        $route2 = $this->prophesize("\\pjdietz\\WellRESTed\\Interfaces\\HandlerInterface");
        $route2->getResponse(Argument::cetera())->willReturn(null);

        $this->request->getPath()->willReturn("/animals/cats/molly");

        $table = new RouteTable();
        $table->addRoute($route1->reveal());
        $table->addRoute($route2->reveal());
        $table->getResponse($this->request->reveal());

        $route1->getResponse(Argument::cetera())->shouldHaveBeenCalled();
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
        $table->addRoute($route1->reveal());
        $table->addRoute($route2->reveal());
        $table->addRoute($route3->reveal());
        $response = $table->getResponse($this->request->reveal());

        $this->assertNotNull($response);
        $route1->getResponse(Argument::cetera())->shouldHaveBeenCalled();
        $route2->getResponse(Argument::cetera())->shouldHaveBeenCalled();
        $route3->getResponse(Argument::cetera())->shouldNotHaveBeenCalled();
    }

    public function testPropagatesArgumentsToStaticRoute()
    {
        $this->route->willImplement("\\pjdietz\\WellRESTed\\Interfaces\\Routes\\StaticRouteInterface");
        $this->route->getPaths()->willReturn(["/cats/"]);
        $this->route->getResponse(Argument::cetera())->willReturn($this->response->reveal());

        $this->request->getPath()->willReturn("/cats/");

        $args = ["cat" => "molly"];

        $table = new RouteTable();
        $table->addRoute($this->route->reveal());
        $table->getResponse($this->request->reveal(), $args);

        $this->route->getResponse($this->request->reveal(), $args)->shouldHaveBeenCalled();
    }

    public function testPropagatesArgumentsToPrefixRoute()
    {
        $this->route->willImplement("\\pjdietz\\WellRESTed\\Interfaces\\Routes\\PrefixRouteInterface");
        $this->route->getPrefixes()->willReturn(["/cats/"]);
        $this->route->getResponse(Argument::cetera())->willReturn($this->response->reveal());

        $this->request->getPath()->willReturn("/cats/");

        $args = ["cat" => "molly"];

        $table = new RouteTable();
        $table->addRoute($this->route->reveal());
        $table->getResponse($this->request->reveal(), $args);

        $this->route->getResponse($this->request->reveal(), $args)->shouldHaveBeenCalled();
    }

    public function testPropagatesArwgumentsToRoute()
    {
        $this->route->getResponse(Argument::cetera())->willReturn($this->response->reveal());
        $this->request->getPath()->willReturn("/cats/");
        $args = ["cat" => "molly"];

        $table = new RouteTable();
        $table->addRoute($this->route->reveal());
        $table->getResponse($this->request->reveal(), $args);

        $this->route->getResponse($this->request->reveal(), $args)->shouldHaveBeenCalled();
    }
}
