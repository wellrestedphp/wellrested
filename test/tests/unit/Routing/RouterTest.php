<?php

namespace WellRESTed\Test\Unit\Routing;

use Prophecy\Argument;
use WellRESTed\Routing\Route\RouteInterface;
use WellRESTed\Routing\Router;

// TODO: register with array of middleware creates stack

/**
 * @coversDefaultClass WellRESTed\Routing\Router
 * @uses WellRESTed\Routing\Router
 * @group routing
 */
class RouterTest extends \PHPUnit_Framework_TestCase
{
    private $dispatcher;
    private $dispatchProvider;
    private $methodMap;
    private $factory;
    private $request;
    private $response;
    private $route;
    private $router;
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

        $this->dispatcher = $this->prophesize('WellRESTed\Dispatching\DispatcherInterface');
        $this->dispatcher->dispatch(Argument::cetera())->will(
            function ($args) {
                list($middleware, $request, $response, $next) = $args;
                return $middleware->dispatch($request, $response, $next);
            }
        );

        $this->dispatchProvider = $this->prophesize('WellRESTed\Dispatching\DispatchProviderInterface');
        $this->dispatchProvider->getDispatcher()->willReturn($this->dispatcher->reveal());

        $this->router = $this->getMockBuilder('WellRESTed\Routing\Router')
            ->setMethods(["getRouteFactory"])
            ->disableOriginalConstructor()
            ->getMock();
        $this->router->expects($this->any())
            ->method("getRouteFactory")
            ->will($this->returnValue($this->factory->reveal()));
        $this->router->__construct($this->dispatchProvider->reveal());
    }

    // ------------------------------------------------------------------------
    // Construction

    /**
     * @covers ::__construct
     * @covers ::getRouteFactory
     * @uses WellRESTed\Routing\Route\RouteFactory
     */
    public function testCreatesInstance()
    {
        $routeMap = new Router($this->dispatchProvider->reveal());
        $this->assertNotNull($routeMap);
    }

    // ------------------------------------------------------------------------
    // Populating

    /**
     * @covers ::register
     * @covers ::getRouteForTarget
     * @covers ::registerRouteForTarget
     */
    public function testCreatesRouteForTarget()
    {
        $this->router->register("GET", "/", "middleware");
        $this->factory->create("/")->shouldHaveBeenCalled();
    }

    /**
     * @covers ::register
     * @covers ::getRouteForTarget
     */
    public function testDoesNotRecreateRouteForExistingTarget()
    {
        $this->router->register("GET", "/", "middleware");
        $this->router->register("POST", "/", "middleware");
        $this->factory->create("/")->shouldHaveBeenCalledTimes(1);
    }

    /**
     * @covers ::register
     */
    public function testPassesMethodAndMiddlewareToMethodMap()
    {
        $this->router->register("GET", "/", "middleware");
        $this->methodMap->register("GET", "middleware")->shouldHaveBeenCalled();
    }

    /**
     * @covers ::register
     */
    public function testCreatesDispatchStackForMiddlewareArray()
    {
        $stack = $this->prophesize('WellRESTed\MiddlewareInterface');
        $this->dispatchProvider->getDispatchStack(Argument::any())->willReturn($stack->reveal());

        $this->router->register("GET", "/", ["middleware1", "middleware2"]);
        $this->methodMap->register("GET", $stack->reveal())->shouldHaveBeenCalled();
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

        $this->router->register("GET", $target, "middleware");
        $this->router->dispatch($this->request->reveal(), $this->response->reveal(), $this->next);

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

        $this->router->register("GET", $target, "middleware");
        $this->router->dispatch($this->request->reveal(), $this->response->reveal(), $this->next);

        $this->route->dispatch($this->request->reveal(), $this->response->reveal(), $this->next)->shouldHaveBeenCalled();
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

        $this->router->register("GET", $target, "middleware");
        $this->router->dispatch($this->request->reveal(), $this->response->reveal(), $this->next);

        $this->route->dispatch($this->request->reveal(), $this->response->reveal(), $this->next)->shouldHaveBeenCalled();
    }

    /**
     * @coversNothing
     */
    public function testDispatchesStaticRouteBeforePrefixRoute()
    {
        $staticRoute = $this->prophesize('WellRESTed\Routing\Route\RouteInterface');
        $staticRoute->getMethodMap()->willReturn($this->methodMap->reveal());
        $staticRoute->getTarget()->willReturn("/cats/");
        $staticRoute->getType()->willReturn(RouteInterface::TYPE_STATIC);
        $staticRoute->dispatch(Argument::cetera())->willReturn();

        $prefixRoute = $this->prophesize('WellRESTed\Routing\Route\RouteInterface');
        $prefixRoute->getMethodMap()->willReturn($this->methodMap->reveal());
        $prefixRoute->getTarget()->willReturn("/cats/*");
        $prefixRoute->getType()->willReturn(RouteInterface::TYPE_PREFIX);
        $prefixRoute->dispatch(Argument::cetera())->willReturn();

        $this->request->getRequestTarget()->willReturn("/cats/");

        $this->factory->create("/cats/")->willReturn($staticRoute->reveal());
        $this->factory->create("/cats/*")->willReturn($prefixRoute->reveal());

        $this->router->register("GET", "/cats/", "middleware");
        $this->router->register("GET", "/cats/*", "middleware");
        $this->router->dispatch($this->request->reveal(), $this->response->reveal(), $this->next);

        $staticRoute->dispatch($this->request->reveal(), $this->response->reveal(), $this->next)->shouldHaveBeenCalled();
    }

    /**
     * @covers ::getPrefixRoute
     */
    public function testDispatchesLongestMatchingPrefixRoute()
    {
        // Note: The longest route is also good for 2 points in Settlers of Catan.

        $shortRoute = $this->prophesize('WellRESTed\Routing\Route\RouteInterface');
        $shortRoute->getMethodMap()->willReturn($this->methodMap->reveal());
        $shortRoute->getTarget()->willReturn("/animals/*");
        $shortRoute->getType()->willReturn(RouteInterface::TYPE_PREFIX);
        $shortRoute->dispatch(Argument::cetera())->willReturn();

        $longRoute = $this->prophesize('WellRESTed\Routing\Route\RouteInterface');
        $longRoute->getMethodMap()->willReturn($this->methodMap->reveal());
        $longRoute->getTarget()->willReturn("/animals/cats/*");
        $longRoute->getType()->willReturn(RouteInterface::TYPE_PREFIX);
        $longRoute->dispatch(Argument::cetera())->willReturn();

        $this->request->getRequestTarget()->willReturn("/animals/cats/molly");

        $this->factory->create("/animals/*")->willReturn($shortRoute->reveal());
        $this->factory->create("/animals/cats/*")->willReturn($longRoute->reveal());

        $this->router->register("GET", "/animals/*", "middleware");
        $this->router->register("GET", "/animals/cats/*", "middleware");
        $this->router->dispatch($this->request->reveal(), $this->response->reveal(), $this->next);

        $longRoute->dispatch($this->request->reveal(), $this->response->reveal(), $this->next)->shouldHaveBeenCalled();
    }

    /**
     * @coversNothing
     */
    public function testDispatchesPrefixRouteBeforePatternRoute()
    {
        $prefixRoute = $this->prophesize('WellRESTed\Routing\Route\RouteInterface');
        $prefixRoute->getMethodMap()->willReturn($this->methodMap->reveal());
        $prefixRoute->getTarget()->willReturn("/cats/*");
        $prefixRoute->getType()->willReturn(RouteInterface::TYPE_PREFIX);
        $prefixRoute->dispatch(Argument::cetera())->willReturn();

        $patternRoute = $this->prophesize('WellRESTed\Routing\Route\RouteInterface');
        $patternRoute->getMethodMap()->willReturn($this->methodMap->reveal());
        $patternRoute->getTarget()->willReturn("/cats/{id}");
        $patternRoute->getType()->willReturn(RouteInterface::TYPE_PATTERN);
        $patternRoute->dispatch(Argument::cetera())->willReturn();

        $this->request->getRequestTarget()->willReturn("/cats/");

        $this->factory->create("/cats/*")->willReturn($prefixRoute->reveal());
        $this->factory->create("/cats/{id}")->willReturn($patternRoute->reveal());

        $this->router->register("GET", "/cats/*", "middleware");
        $this->router->register("GET", "/cats/{id}", "middleware");
        $this->router->dispatch($this->request->reveal(), $this->response->reveal(), $this->next);

        $prefixRoute->dispatch($this->request->reveal(), $this->response->reveal(), $this->next)->shouldHaveBeenCalled();
    }

    /**
     * @coversNothing
     */
    public function testDispatchesFirstMatchingPatternRoute()
    {
        $patternRoute1 = $this->prophesize('WellRESTed\Routing\Route\RouteInterface');
        $patternRoute1->getMethodMap()->willReturn($this->methodMap->reveal());
        $patternRoute1->getTarget()->willReturn("/cats/{id}");
        $patternRoute1->getType()->willReturn(RouteInterface::TYPE_PATTERN);
        $patternRoute1->matchesRequestTarget(Argument::any())->willReturn(true);
        $patternRoute1->dispatch(Argument::cetera())->willReturn();

        $patternRoute2 = $this->prophesize('WellRESTed\Routing\Route\RouteInterface');
        $patternRoute2->getMethodMap()->willReturn($this->methodMap->reveal());
        $patternRoute2->getTarget()->willReturn("/cats/{name}");
        $patternRoute2->getType()->willReturn(RouteInterface::TYPE_PATTERN);
        $patternRoute2->matchesRequestTarget(Argument::any())->willReturn(true);
        $patternRoute2->dispatch(Argument::cetera())->willReturn();

        $this->request->getRequestTarget()->willReturn("/cats/molly");

        $this->factory->create("/cats/{id}")->willReturn($patternRoute1->reveal());
        $this->factory->create("/cats/{name}")->willReturn($patternRoute2->reveal());

        $this->router->register("GET", "/cats/{id}", "middleware");
        $this->router->register("GET", "/cats/{name}", "middleware");
        $this->router->dispatch($this->request->reveal(), $this->response->reveal(), $this->next);

        $patternRoute1->dispatch($this->request->reveal(), $this->response->reveal(), $this->next)->shouldHaveBeenCalled();
    }

    /**
     * @coversNothing
     */
    public function testStopsTestingPatternsAfterFirstSuccessfulMatch()
    {
        $patternRoute1 = $this->prophesize('WellRESTed\Routing\Route\RouteInterface');
        $patternRoute1->getMethodMap()->willReturn($this->methodMap->reveal());
        $patternRoute1->getTarget()->willReturn("/cats/{id}");
        $patternRoute1->getType()->willReturn(RouteInterface::TYPE_PATTERN);
        $patternRoute1->matchesRequestTarget(Argument::any())->willReturn(true);
        $patternRoute1->dispatch(Argument::cetera())->willReturn();

        $patternRoute2 = $this->prophesize('WellRESTed\Routing\Route\RouteInterface');
        $patternRoute2->getMethodMap()->willReturn($this->methodMap->reveal());
        $patternRoute2->getTarget()->willReturn("/cats/{name}");
        $patternRoute2->getType()->willReturn(RouteInterface::TYPE_PATTERN);
        $patternRoute2->matchesRequestTarget(Argument::any())->willReturn(true);
        $patternRoute2->dispatch(Argument::cetera())->willReturn();

        $this->request->getRequestTarget()->willReturn("/cats/molly");

        $this->factory->create("/cats/{id}")->willReturn($patternRoute1->reveal());
        $this->factory->create("/cats/{name}")->willReturn($patternRoute2->reveal());

        $this->router->register("GET", "/cats/{id}", "middleware");
        $this->router->register("GET", "/cats/{name}", "middleware");
        $this->router->dispatch($this->request->reveal(), $this->response->reveal(), $this->next);

        $patternRoute2->matchesRequestTarget(Argument::any())->shouldNotHaveBeenCalled();
    }

    // ------------------------------------------------------------------------
    // No Matching Routes

    /**
     * @covers ::dispatch
     * @covers ::getStaticRoute
     * @covers ::getPrefixRoute
     */
    public function testResponds404WhenNoRouteMatches()
    {
        $this->response->withStatus(Argument::any())->willReturn($this->response->reveal());
        $this->router->dispatch($this->request->reveal(), $this->response->reveal(), $this->next);
        $this->response->withStatus(404)->shouldHaveBeenCalled();
    }

    /**
     * @covers ::dispatch
     * @covers ::getStaticRoute
     * @covers ::getPrefixRoute
     */
    public function testCallsNextWhenNoRouteMatches()
    {
        $calledNext = false;
        $next = function ($request, $response) use (&$calledNext) {
            $calledNext = true;
            return $response;
        };

        $this->response->withStatus(Argument::any())->willReturn($this->response->reveal());
        $this->router->dispatch($this->request->reveal(), $this->response->reveal(), $next);
        $this->assertTrue($calledNext);
    }

    public function testRegisterIsFluid()
    {
        $router = $this->router
            ->register("GET", "/", "middleware")
            ->register("POST", "/", "middleware");
        $this->assertSame($this->router, $router);
    }
}
