<?php

namespace WellRESTed\Test\Unit\Routing;

use Prophecy\Argument;
use WellRESTed\Routing\Route\RouteInterface;
use WellRESTed\Routing\Router;

/**
 * @coversDefaultClass WellRESTed\Routing\Router
 * @uses WellRESTed\Routing\Router
 * @group routing
 */
class RouterTest extends \PHPUnit_Framework_TestCase
{
    private $dispatcher;
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
        $this->route->__invoke(Argument::cetera())->willReturn();
        $this->route->getMethodMap()->willReturn($this->methodMap->reveal());
        $this->route->getType()->willReturn(RouteInterface::TYPE_STATIC);
        $this->route->getTarget()->willReturn("/");
        $this->route->getPathVariables()->willReturn([]);

        $this->factory = $this->prophesize('WellRESTed\Routing\Route\RouteFactory');
        $this->factory->create(Argument::any())->willReturn($this->route->reveal());

        $this->request = $this->prophesize('Psr\Http\Message\ServerRequestInterface');
        $this->request->withAttribute(Argument::cetera())->willReturn($this->request->reveal());

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

        $this->router = $this->getMockBuilder('WellRESTed\Routing\Router')
            ->setMethods(["getRouteFactory"])
            ->disableOriginalConstructor()
            ->getMock();
        $this->router->expects($this->any())
            ->method("getRouteFactory")
            ->will($this->returnValue($this->factory->reveal()));
        $this->router->__construct($this->dispatcher->reveal());
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
        $router = new Router($this->dispatcher->reveal());
        $this->assertNotNull($router);
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

    // ------------------------------------------------------------------------
    // Dispatching

    /**
     * @covers ::__invoke
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
        $this->router->__invoke($this->request->reveal(), $this->response->reveal(), $this->next);

        $this->route->__invoke($this->request->reveal(), $this->response->reveal(), $this->next)->shouldHaveBeenCalled();
    }

    /**
     * @covers ::__invoke
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
        $this->router->__invoke($this->request->reveal(), $this->response->reveal(), $this->next);

        $this->route->__invoke($this->request->reveal(), $this->response->reveal(), $this->next)->shouldHaveBeenCalled();
    }

    /**
     * @covers ::__invoke
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
        $this->router->__invoke($this->request->reveal(), $this->response->reveal(), $this->next);

        $this->route->__invoke($this->request->reveal(), $this->response->reveal(), $this->next)->shouldHaveBeenCalled();
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
        $staticRoute->__invoke(Argument::cetera())->willReturn();

        $prefixRoute = $this->prophesize('WellRESTed\Routing\Route\RouteInterface');
        $prefixRoute->getMethodMap()->willReturn($this->methodMap->reveal());
        $prefixRoute->getTarget()->willReturn("/cats/*");
        $prefixRoute->getType()->willReturn(RouteInterface::TYPE_PREFIX);
        $prefixRoute->__invoke(Argument::cetera())->willReturn();

        $this->request->getRequestTarget()->willReturn("/cats/");

        $this->factory->create("/cats/")->willReturn($staticRoute->reveal());
        $this->factory->create("/cats/*")->willReturn($prefixRoute->reveal());

        $this->router->register("GET", "/cats/", "middleware");
        $this->router->register("GET", "/cats/*", "middleware");
        $this->router->__invoke($this->request->reveal(), $this->response->reveal(), $this->next);

        $staticRoute->__invoke($this->request->reveal(), $this->response->reveal(), $this->next)->shouldHaveBeenCalled();
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
        $shortRoute->__invoke(Argument::cetera())->willReturn();

        $longRoute = $this->prophesize('WellRESTed\Routing\Route\RouteInterface');
        $longRoute->getMethodMap()->willReturn($this->methodMap->reveal());
        $longRoute->getTarget()->willReturn("/animals/cats/*");
        $longRoute->getType()->willReturn(RouteInterface::TYPE_PREFIX);
        $longRoute->__invoke(Argument::cetera())->willReturn();

        $this->request->getRequestTarget()->willReturn("/animals/cats/molly");

        $this->factory->create("/animals/*")->willReturn($shortRoute->reveal());
        $this->factory->create("/animals/cats/*")->willReturn($longRoute->reveal());

        $this->router->register("GET", "/animals/*", "middleware");
        $this->router->register("GET", "/animals/cats/*", "middleware");
        $this->router->__invoke($this->request->reveal(), $this->response->reveal(), $this->next);

        $longRoute->__invoke($this->request->reveal(), $this->response->reveal(), $this->next)->shouldHaveBeenCalled();
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
        $prefixRoute->__invoke(Argument::cetera())->willReturn();

        $patternRoute = $this->prophesize('WellRESTed\Routing\Route\RouteInterface');
        $patternRoute->getMethodMap()->willReturn($this->methodMap->reveal());
        $patternRoute->getTarget()->willReturn("/cats/{id}");
        $patternRoute->getType()->willReturn(RouteInterface::TYPE_PATTERN);
        $patternRoute->__invoke(Argument::cetera())->willReturn();

        $this->request->getRequestTarget()->willReturn("/cats/");

        $this->factory->create("/cats/*")->willReturn($prefixRoute->reveal());
        $this->factory->create("/cats/{id}")->willReturn($patternRoute->reveal());

        $this->router->register("GET", "/cats/*", "middleware");
        $this->router->register("GET", "/cats/{id}", "middleware");
        $this->router->__invoke($this->request->reveal(), $this->response->reveal(), $this->next);

        $prefixRoute->__invoke($this->request->reveal(), $this->response->reveal(), $this->next)->shouldHaveBeenCalled();
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
        $patternRoute1->getPathVariables()->willReturn([]);
        $patternRoute1->matchesRequestTarget(Argument::any())->willReturn(true);
        $patternRoute1->__invoke(Argument::cetera())->willReturn();

        $patternRoute2 = $this->prophesize('WellRESTed\Routing\Route\RouteInterface');
        $patternRoute2->getMethodMap()->willReturn($this->methodMap->reveal());
        $patternRoute2->getTarget()->willReturn("/cats/{name}");
        $patternRoute2->getType()->willReturn(RouteInterface::TYPE_PATTERN);
        $patternRoute2->getPathVariables()->willReturn([]);
        $patternRoute2->matchesRequestTarget(Argument::any())->willReturn(true);
        $patternRoute2->__invoke(Argument::cetera())->willReturn();

        $this->request->getRequestTarget()->willReturn("/cats/molly");

        $this->factory->create("/cats/{id}")->willReturn($patternRoute1->reveal());
        $this->factory->create("/cats/{name}")->willReturn($patternRoute2->reveal());

        $this->router->register("GET", "/cats/{id}", "middleware");
        $this->router->register("GET", "/cats/{name}", "middleware");
        $this->router->__invoke($this->request->reveal(), $this->response->reveal(), $this->next);

        $patternRoute1->__invoke($this->request->reveal(), $this->response->reveal(), $this->next)->shouldHaveBeenCalled();
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
        $patternRoute1->getPathVariables()->willReturn([]);
        $patternRoute1->matchesRequestTarget(Argument::any())->willReturn(true);
        $patternRoute1->__invoke(Argument::cetera())->willReturn();

        $patternRoute2 = $this->prophesize('WellRESTed\Routing\Route\RouteInterface');
        $patternRoute2->getMethodMap()->willReturn($this->methodMap->reveal());
        $patternRoute2->getTarget()->willReturn("/cats/{name}");
        $patternRoute2->getType()->willReturn(RouteInterface::TYPE_PATTERN);
        $patternRoute2->getPathVariables()->willReturn([]);
        $patternRoute2->matchesRequestTarget(Argument::any())->willReturn(true);
        $patternRoute2->__invoke(Argument::cetera())->willReturn();

        $this->request->getRequestTarget()->willReturn("/cats/molly");

        $this->factory->create("/cats/{id}")->willReturn($patternRoute1->reveal());
        $this->factory->create("/cats/{name}")->willReturn($patternRoute2->reveal());

        $this->router->register("GET", "/cats/{id}", "middleware");
        $this->router->register("GET", "/cats/{name}", "middleware");
        $this->router->__invoke($this->request->reveal(), $this->response->reveal(), $this->next);

        $patternRoute2->matchesRequestTarget(Argument::any())->shouldNotHaveBeenCalled();
    }

    /**
     * @covers ::__invoke
     * @covers ::registerRouteForTarget
     */
    public function testMatchesPathAgainstRouteWithoutQuery()
    {
        $target = "/my/path?cat=molly&dog=bear";

        $this->request->getRequestTarget()->willReturn($target);
        $this->route->getTarget()->willReturn($target);
        $this->route->getType()->willReturn(RouteInterface::TYPE_PATTERN);
        $this->route->matchesRequestTarget(Argument::cetera())->willReturn(true);

        $this->router->register("GET", $target, "middleware");
        $this->router->__invoke($this->request->reveal(), $this->response->reveal(), $this->next);

        $this->route->matchesRequestTarget("/my/path")->shouldHaveBeenCalled();
    }

    // ------------------------------------------------------------------------
    // Path Variables

    /**
     * @covers ::__invoke
     * @dataProvider pathVariableProvider
     */
    public function testSetPathVariablesAttributeIndividually($name, $value)
    {
        $attributeName = "pathVariables";

        $target = "/";
        $variables = [
            "id" => "1024",
            "name" => "Molly"
        ];

        $this->request->getRequestTarget()->willReturn($target);
        $this->route->getTarget()->willReturn($target);
        $this->route->getType()->willReturn(RouteInterface::TYPE_PATTERN);
        $this->route->matchesRequestTarget(Argument::cetera())->willReturn(true);
        $this->route->getPathVariables()->willReturn($variables);

        $this->router->__construct($this->dispatcher->reveal());
        $this->router->register("GET", $target, "middleware");
        $this->router->__invoke($this->request->reveal(), $this->response->reveal(), $this->next);

        $this->request->withAttribute($name, $value)->shouldHaveBeenCalled();
    }

    public function pathVariableProvider()
    {
        return [
            ["id", "1024"],
            ["name", "Molly"]
        ];
    }

    /**
     * @covers ::__invoke
     */
    public function testSetPathVariablesAttributeAsArray()
    {
        $attributeName = "pathVariables";

        $target = "/";
        $variables = [
            "id" => "1024",
            "name" => "Molly"
        ];

        $this->request->getRequestTarget()->willReturn($target);
        $this->route->getTarget()->willReturn($target);
        $this->route->getType()->willReturn(RouteInterface::TYPE_PATTERN);
        $this->route->matchesRequestTarget(Argument::cetera())->willReturn(true);
        $this->route->getPathVariables()->willReturn($variables);

        $this->router->__construct($this->dispatcher->reveal(), $attributeName);
        $this->router->register("GET", $target, "middleware");
        $this->router->__invoke($this->request->reveal(), $this->response->reveal(), $this->next);

        $this->request->withAttribute("pathVariables", $variables)->shouldHaveBeenCalled();
    }

    // ------------------------------------------------------------------------
    // No Matching Routes

    /**
     * @covers ::__invoke
     * @covers ::getStaticRoute
     * @covers ::getPrefixRoute
     */
    public function testResponds404WhenNoRouteMatches()
    {
        $this->request->getRequestTarget()->willReturn("/no/match");
        $this->response->withStatus(Argument::any())->willReturn($this->response->reveal());
        $this->router->__invoke($this->request->reveal(), $this->response->reveal(), $this->next);
        $this->response->withStatus(404)->shouldHaveBeenCalled();
    }

    /**
     * @covers ::__invoke
     * @covers ::getStaticRoute
     * @covers ::getPrefixRoute
     */
    public function testStopsPropagatingWhenNoRouteMatches()
    {
        $calledNext = false;
        $next = function ($request, $response) use (&$calledNext) {
            $calledNext = true;
            return $response;
        };

        $this->request->getRequestTarget()->willReturn("/no/match");
        $this->response->withStatus(Argument::any())->willReturn($this->response->reveal());
        $this->router->__invoke($this->request->reveal(), $this->response->reveal(), $next);
        $this->assertFalse($calledNext);
    }

    public function testRegisterIsFluid()
    {
        $router = $this->router
            ->register("GET", "/", "middleware")
            ->register("POST", "/", "middleware");
        $this->assertSame($this->router, $router);
    }
}
