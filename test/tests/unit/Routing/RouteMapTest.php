<?php

namespace WellRESTed\Test\Unit\Routing;

use Prophecy\Argument;
use WellRESTed\Routing\Route\RouteInterface;
use WellRESTed\Routing\RouteMap;

// Test dispatch orders (static before prefix, prefix before pattern)
// Test dispatches first matching pattern route

/**
 * @coversDefaultClass WellRESTed\Routing\RouteMap
 * @uses WellRESTed\Routing\RouteMap
 */
class RouteMapTest extends \PHPUnit_Framework_TestCase
{
    private $methodMap;
    private $factory;
    private $request;
    private $response;
    private $route;
    private $routeMap;
    private $next;

    public function setUp()
    {
        parent::setUp();

        $this->methodMap = $this->prophesize('WellRESTed\Routing\MethodMapInterface');
        $this->methodMap->register(Argument::cetera());

        $this->route = $this->prophesize('WellRESTed\Routing\Route\RouteInterface');
        $this->route->dispatch(Argument::cetera())->willReturn();
        $this->route->getMethodMap()->willReturn($this->methodMap->reveal());
        $this->route->getType()->willReturn(RouteInterface::TYPE_STATIC);
        $this->route->getTarget()->willReturn("/");

        $this->factory = $this->prophesize('WellRESTed\Routing\Route\RouteFactory');
        $this->factory->create(Argument::any())->willReturn($this->route->reveal());

        $this->request = $this->prophesize('Psr\Http\Message\ServerRequestInterface');
        $this->response = $this->prophesize('Psr\Http\Message\ResponseInterface');
        $this->next = function ($request, $response) {
            return $response;
        };

        $this->routeMap = $this->getMockBuilder('WellRESTed\Routing\RouteMap')
            ->setMethods(["getRouteFactory"])
            ->disableOriginalConstructor()
            ->getMock();
        $this->routeMap->expects($this->any())
            ->method("getRouteFactory")
            ->will($this->returnValue($this->factory->reveal()));
        $this->routeMap->__construct();
    }

    // ------------------------------------------------------------------------
    // Construction

    /**
     * @covers ::__construct
     * @covers ::getRouteFactory
     */
    public function testCreatesInstance()
    {
        $routeMap = new RouteMap();
        $this->assertNotNull($routeMap);
    }

    // ------------------------------------------------------------------------
    // Populating

    /**
     * @covers ::add
     * @covers ::getRouteForTarget
     * @covers ::registerRouteForTarget
     */
    public function testAddCreatesRouteForTarget()
    {
        $this->routeMap->add("GET", "/", "middleware");
        $this->factory->create("/")->shouldHaveBeenCalled();
    }

    /**
     * @covers ::add
     * @covers ::getRouteForTarget
     */
    public function testAddDoesNotRecreateRouteForExistingTarget()
    {
        $this->routeMap->add("GET", "/", "middleware");
        $this->routeMap->add("POST", "/", "middleware");
        $this->factory->create("/")->shouldHaveBeenCalledTimes(1);
    }

    /**
     * @covers ::add
     */
    public function testAddPassesMethodAndMiddlewareToMethodMap()
    {
        $this->routeMap->add("GET", "/", "middleware");
        $this->methodMap->register("GET", "middleware")->shouldHaveBeenCalled();
    }

    // ------------------------------------------------------------------------
    // Dispatching

    /**
     * @covers ::dispatch
     * @covers ::getStaticRoute
     * @covers ::registerRouteForTarget
     */
    public function testDispatchesStaticRoute()
    {
        $target = "/";

        $this->request->getRequestTarget()->willReturn($target);
        $this->route->getTarget()->willReturn($target);
        $this->route->getType()->willReturn(RouteInterface::TYPE_STATIC);

        $this->routeMap->add("GET", $target, "middleware");
        $this->routeMap->dispatch($this->request->reveal(), $this->response->reveal(), $this->next);

        $this->route->dispatch($this->request->reveal(), $this->response->reveal(), $this->next)->shouldHaveBeenCalled();
    }

    /**
     * @covers ::dispatch
     * @covers ::getPrefixRoute
     * @covers ::registerRouteForTarget
     */
    public function testDispatchesPrefixRoute()
    {
        $target = "/animals/cats/*";
        $this->request->getRequestTarget()->willReturn("/animals/cats/molly");
        $this->route->getTarget()->willReturn($target);
        $this->route->getType()->willReturn(RouteInterface::TYPE_PREFIX);

        $this->routeMap->add("GET", $target, "middleware");
        $this->routeMap->dispatch($this->request->reveal(), $this->response->reveal(), $this->next);

        $this->route->dispatch($this->request->reveal(), $this->response->reveal(), $this->next)->shouldHaveBeenCalled();
    }

    /**
     * @covers ::getPrefixRoute
     */
    public function testDispatchesLongestMatchingPrefixRoute()
    {
        $routeAnimals = $this->prophesize('WellRESTed\Routing\Route\RouteInterface');
        $routeAnimals->getMethodMap()->willReturn($this->methodMap->reveal());
        $routeAnimals->getTarget()->willReturn("/animals/*");
        $routeAnimals->getType()->willReturn(RouteInterface::TYPE_PREFIX);
        $routeAnimals->dispatch(Argument::cetera())->willReturn();

        $routeCats = $this->prophesize('WellRESTed\Routing\Route\RouteInterface');
        $routeCats->getMethodMap()->willReturn($this->methodMap->reveal());
        $routeCats->getTarget()->willReturn("/animals/cats/*");
        $routeCats->getType()->willReturn(RouteInterface::TYPE_PREFIX);
        $routeCats->dispatch(Argument::cetera())->willReturn();

        $this->request->getRequestTarget()->willReturn("/animals/cats/molly");

        $this->factory->create("/animals/*")->willReturn($routeAnimals->reveal());
        $this->factory->create("/animals/cats/*")->willReturn($routeCats->reveal());

        $this->routeMap->add("GET", "/animals/*", "middleware");
        $this->routeMap->add("GET", "/animals/cats/*", "middleware");
        $this->routeMap->dispatch($this->request->reveal(), $this->response->reveal(), $this->next);

        $routeCats->dispatch($this->request->reveal(), $this->response->reveal(), $this->next)->shouldHaveBeenCalled();
    }

    /**
     * @covers ::dispatch
     * @covers ::registerRouteForTarget
     */
    public function testDispatchesPatternRoute()
    {
        $target = "/";

        $this->request->getRequestTarget()->willReturn($target);
        $this->route->getTarget()->willReturn($target);
        $this->route->getType()->willReturn(RouteInterface::TYPE_PATTERN);
        $this->route->matchesRequestTarget(Argument::cetera())->willReturn(true);

        $this->routeMap->add("GET", $target, "middleware");
        $this->routeMap->dispatch($this->request->reveal(), $this->response->reveal(), $this->next);

        $this->route->dispatch($this->request->reveal(), $this->response->reveal(), $this->next)->shouldHaveBeenCalled();
    }

    /**
     * @covers ::dispatch
     * @covers ::getStaticRoute
     * @covers ::getPrefixRoute
     */
    public function testResponds404WhenNoRouteMatches()
    {
        $this->response->withStatus(Argument::any())->willReturn($this->response->reveal());
        $this->routeMap->dispatch($this->request->reveal(), $this->response->reveal(), $this->next);
        $this->response->withStatus(404)->shouldHaveBeenCalled();
    }
}
