<?php

namespace WellRESTed\Test\Unit\Routing;

use Prophecy\Argument;
use WellRESTed\Routing\Route\TemplateRoute;
use WellRESTed\Routing\RouteTable;

class RouteTableTest extends \PHPUnit_Framework_TestCase
{
    private $request;
    private $response;

    public function setUp()
    {
        $this->request = $this->prophesize("\\Psr\\Http\\Message\\ServerRequestInterface");
        $this->response = $this->prophesize("\\Psr\\Http\\Message\\ResponseInterface");
    }

    public function testMatchesStaticRoute()
    {
        $route = $this->prophesize("\\WellRESTed\\Routing\\Route\\StaticRouteInterface");
        $route->getPath()->willReturn("/cats/");
        $route->dispatch(Argument::cetera())->willReturn();

        $this->request->getRequestTarget()->willReturn("/cats/");

        $table = new RouteTable();
        $table->addStaticRoute($route->reveal());
        $table->dispatch($this->request->reveal(), $this->response->reveal());

        $route->dispatch($this->request->reveal(),  $this->response->reveal())->shouldHaveBeenCalled();
    }

    public function testMatchesPrefixRoute()
    {
        $route = $this->prophesize("\\WellRESTed\\Routing\\Route\\PrefixRouteInterface");
        $route->getPrefix()->willReturn("/cats/");
        $route->dispatch(Argument::cetera())->willReturn();

        $this->request->getRequestTarget()->willReturn("/cats/molly");

        $table = new RouteTable();
        $table->addPrefixRoute($route->reveal());
        $table->dispatch($this->request->reveal(), $this->response->reveal());

        $route->dispatch($this->request->reveal(),  $this->response->reveal())->shouldHaveBeenCalled();
    }

    public function testMatchesBestPrefixRoute()
    {
        $route1 = $this->prophesize("\\WellRESTed\\Routing\\Route\\PrefixRouteInterface");
        $route1->getPrefix()->willReturn("/animals/");
        $route1->dispatch(Argument::cetera())->willReturn();

        $route2 = $this->prophesize("\\WellRESTed\\Routing\\Route\\PrefixRouteInterface");
        $route2->getPrefix()->willReturn("/animals/cats/");
        $route2->dispatch(Argument::cetera())->willReturn();

        $this->request->getRequestTarget()->willReturn("/animals/cats/molly");

        $table = new RouteTable();
        $table->addPrefixRoute($route1->reveal());
        $table->addPrefixRoute($route2->reveal());
        $table->dispatch($this->request->reveal(), $this->response->reveal());

        $route1->dispatch(Argument::cetera())->shouldNotHaveBeenCalled();
        $route2->dispatch(Argument::cetera())->shouldHaveBeenCalled();
    }

    public function testMatchesStaticRouteBeforePrefixRoute()
    {
        $route1 = $this->prophesize("\\WellRESTed\\Routing\\Route\\PrefixRouteInterface");
        $route1->getPrefix()->willReturn("/animals/cats/");
        $route1->dispatch(Argument::cetera())->willReturn();

        $route2 = $this->prophesize("\\WellRESTed\\Routing\\Route\\StaticRouteInterface");
        $route2->getPath()->willReturn("/animals/cats/molly");
        $route2->dispatch(Argument::cetera())->willReturn();

        $this->request->getRequestTarget()->willReturn("/animals/cats/molly");

        $table = new RouteTable();
        $table->addPrefixRoute($route1->reveal());
        $table->addStaticRoute($route2->reveal());
        $table->dispatch($this->request->reveal(), $this->response->reveal());

        $route1->dispatch(Argument::cetera())->shouldNotHaveBeenCalled();
        $route2->dispatch(Argument::cetera())->shouldHaveBeenCalled();
    }

    public function testMatchesPrefixRouteBeforeRoute()
    {
        $route1 = $this->prophesize("\\WellRESTed\\Routing\\Route\\PrefixRouteInterface");
        $route1->getPrefix()->willReturn("/animals/cats/");
        $route1->dispatch(Argument::cetera())->willReturn();

        $route2 = $this->prophesize("\\WellRESTed\\Routing\\Route\\RouteInterface");
        $route2->matchesRequestTarget(Argument::cetera())->willReturn(true);

        $this->request->getRequestTarget()->willReturn("/animals/cats/molly");

        $table = new RouteTable();
        $table->addPrefixRoute($route1->reveal());
        $table->addRoute($route2->reveal());
        $table->dispatch($this->request->reveal(), $this->response->reveal());

        $route1->dispatch(Argument::cetera())->shouldHaveBeenCalled();
        $route2->dispatch(Argument::cetera())->shouldNotHaveBeenCalled();
    }

    /**
     * @uses WellRESTed\Routing\Route\TemplateRoute
     * @uses WellRESTed\Routing\Route\RegexRoute
     * @uses WellRESTed\Routing\Route\Route
     */
    public function testAddsCapturesAsRequestAttributes()
    {
        // This test needs to read the result of the $captures parameter which
        // is passed by reference. This is not so eary so mock, so the test
        // will use an actual TemplateRoute.

        $middleware = $this->prophesize("\\WellRESTed\\Routing\\MiddlewareInterface");
        $route = new TemplateRoute("/cats/{id}", $middleware->reveal());

        $this->request->withAttribute(Argument::cetera())->willReturn($this->request->reveal());
        $this->request->getRequestTarget()->willReturn("/cats/molly");

        $table = new RouteTable();
        $table->addRoute($route);
        $table->dispatch($this->request->reveal(), $this->response->reveal());

        $this->request->withAttribute("id", "molly")->shouldHaveBeenCalled();
    }

    public function testDispatchedFirstMatchingRoute()
    {
        $route1 = $this->prophesize("\\WellRESTed\\Routing\\Route\\RouteInterface");
        $route1->matchesRequestTarget(Argument::cetera())->willReturn(false);

        $route2 = $this->prophesize("\\WellRESTed\\Routing\\Route\\RouteInterface");
        $route2->matchesRequestTarget(Argument::cetera())->willReturn(true);
        $route2->dispatch(Argument::cetera())->willReturn();

        $route3 = $this->prophesize("\\WellRESTed\\Routing\\Route\\RouteInterface");
        $route3->matchesRequestTarget(Argument::cetera())->willReturn(false);

        $this->request->getRequestTarget()->willReturn("/");

        $table = new RouteTable();
        $table->addRoute($route1->reveal());
        $table->addRoute($route2->reveal());
        $table->addRoute($route3->reveal());
        $table->dispatch($this->request->reveal(), $this->response->reveal());

        $route1->dispatch(Argument::cetera())->shouldNotHaveBeenCalled();
        $route2->dispatch(Argument::cetera())->shouldHaveBeenCalled();
        $route3->dispatch(Argument::cetera())->shouldNotHaveBeenCalled();
    }
}
