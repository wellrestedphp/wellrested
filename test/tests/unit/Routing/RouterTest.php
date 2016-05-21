<?php

namespace WellRESTed\Test\Unit\Routing;

use Prophecy\Argument;
use WellRESTed\Dispatching\Dispatcher;
use WellRESTed\Message\Response;
use WellRESTed\Message\ServerRequest;
use WellRESTed\Routing\Route\RouteInterface;
use WellRESTed\Routing\Router;
use WellRESTed\Test\Doubles\NextMock;

/**
 * @covers WellRESTed\Routing\Router
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

        $this->request = new ServerRequest();
        $this->response = new Response();
        $this->next = new NextMock();

        $this->router = $this->getMockBuilder('WellRESTed\Routing\Router')
            ->setMethods(["getRouteFactory"])
            ->disableOriginalConstructor()
            ->getMock();
        $this->router->expects($this->any())
            ->method("getRouteFactory")
            ->will($this->returnValue($this->factory->reveal()));
        $this->router->__construct(new Dispatcher());
    }

    // ------------------------------------------------------------------------
    // Construction

    public function testCreatesInstance()
    {
        $router = new Router(new Dispatcher());
        $this->assertNotNull($router);
    }

    // ------------------------------------------------------------------------
    // Populating

    public function testCreatesRouteForTarget()
    {
        $this->router->register("GET", "/", "middleware");
        $this->factory->create("/")->shouldHaveBeenCalled();
    }

    public function testDoesNotRecreateRouteForExistingTarget()
    {
        $this->router->register("GET", "/", "middleware");
        $this->router->register("POST", "/", "middleware");
        $this->factory->create("/")->shouldHaveBeenCalledTimes(1);
    }

    public function testPassesMethodAndMiddlewareToMethodMap()
    {
        $this->router->register("GET", "/", "middleware");
        $this->methodMap->register("GET", "middleware")->shouldHaveBeenCalled();
    }

    // ------------------------------------------------------------------------
    // Dispatching

    public function testDispatchesStaticRoute()
    {
        $target = "/";
        $this->request = $this->request->withRequestTarget($target);

        $this->route->getTarget()->willReturn($target);
        $this->route->getType()->willReturn(RouteInterface::TYPE_STATIC);

        $this->router->register("GET", $target, "middleware");
        $this->router->__invoke($this->request, $this->response, $this->next);

        $this->route->__invoke($this->request, $this->response, $this->next)->shouldHaveBeenCalled();
    }

    public function testDispatchesPrefixRoute()
    {
        $target = "/animals/cats/*";
        $this->request = $this->request->withRequestTarget("/animals/cats/molly");

        $this->route->getTarget()->willReturn($target);
        $this->route->getType()->willReturn(RouteInterface::TYPE_PREFIX);

        $this->router->register("GET", $target, "middleware");
        $this->router->__invoke($this->request, $this->response, $this->next);

        $this->route->__invoke($this->request, $this->response, $this->next)->shouldHaveBeenCalled();
    }

    public function testDispatchesPatternRoute()
    {
        $target = "/";
        $this->request = $this->request->withRequestTarget($target);

        $this->route->getTarget()->willReturn($target);
        $this->route->getType()->willReturn(RouteInterface::TYPE_PATTERN);
        $this->route->matchesRequestTarget(Argument::cetera())->willReturn(true);

        $this->router->register("GET", $target, "middleware");
        $this->router->__invoke($this->request, $this->response, $this->next);

        $this->route->__invoke($this->request, $this->response, $this->next)->shouldHaveBeenCalled();
    }

    /** @coversNothing */
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

        $this->request = $this->request->withRequestTarget("/cats/");

        $this->factory->create("/cats/")->willReturn($staticRoute->reveal());
        $this->factory->create("/cats/*")->willReturn($prefixRoute->reveal());

        $this->router->register("GET", "/cats/", "middleware");
        $this->router->register("GET", "/cats/*", "middleware");
        $this->router->__invoke($this->request, $this->response, $this->next);

        $staticRoute->__invoke($this->request, $this->response, $this->next)->shouldHaveBeenCalled();
    }

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

        $this->request = $this->request->withRequestTarget("/animals/cats/molly");

        $this->factory->create("/animals/*")->willReturn($shortRoute->reveal());
        $this->factory->create("/animals/cats/*")->willReturn($longRoute->reveal());

        $this->router->register("GET", "/animals/*", "middleware");
        $this->router->register("GET", "/animals/cats/*", "middleware");
        $this->router->__invoke($this->request, $this->response, $this->next);

        $longRoute->__invoke($this->request, $this->response, $this->next)->shouldHaveBeenCalled();
    }

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

        $this->request = $this->request->withRequestTarget("/cats/");

        $this->factory->create("/cats/*")->willReturn($prefixRoute->reveal());
        $this->factory->create("/cats/{id}")->willReturn($patternRoute->reveal());

        $this->router->register("GET", "/cats/*", "middleware");
        $this->router->register("GET", "/cats/{id}", "middleware");
        $this->router->__invoke($this->request, $this->response, $this->next);

        $prefixRoute->__invoke($this->request, $this->response, $this->next)->shouldHaveBeenCalled();
    }

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

        $this->request = $this->request->withRequestTarget("/cats/molly");

        $this->factory->create("/cats/{id}")->willReturn($patternRoute1->reveal());
        $this->factory->create("/cats/{name}")->willReturn($patternRoute2->reveal());

        $this->router->register("GET", "/cats/{id}", "middleware");
        $this->router->register("GET", "/cats/{name}", "middleware");
        $this->router->__invoke($this->request, $this->response, $this->next);

        $patternRoute1->__invoke($this->request, $this->response, $this->next)->shouldHaveBeenCalled();
    }

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

        $this->request = $this->request->withRequestTarget("/cats/molly");

        $this->factory->create("/cats/{id}")->willReturn($patternRoute1->reveal());
        $this->factory->create("/cats/{name}")->willReturn($patternRoute2->reveal());

        $this->router->register("GET", "/cats/{id}", "middleware");
        $this->router->register("GET", "/cats/{name}", "middleware");
        $this->router->__invoke($this->request, $this->response, $this->next);

        $patternRoute2->matchesRequestTarget(Argument::any())->shouldNotHaveBeenCalled();
    }

    public function testMatchesPathAgainstRouteWithoutQuery()
    {
        $target = "/my/path?cat=molly&dog=bear";

        $this->request = $this->request->withRequestTarget($target);

        $this->route->getTarget()->willReturn($target);
        $this->route->getType()->willReturn(RouteInterface::TYPE_PATTERN);
        $this->route->matchesRequestTarget(Argument::cetera())->willReturn(true);

        $this->router->register("GET", $target, "middleware");
        $this->router->__invoke($this->request, $this->response, $this->next);

        $this->route->matchesRequestTarget("/my/path")->shouldHaveBeenCalled();
    }

    // ------------------------------------------------------------------------
    // Path Variables

    /** @dataProvider pathVariableProvider */
    public function testSetPathVariablesAttributeIndividually($name, $value)
    {
        $target = "/";
        $variables = [
            "id" => "1024",
            "name" => "Molly"
        ];

        $this->request = $this->request->withRequestTarget($target);

        $this->route->getTarget()->willReturn($target);
        $this->route->getType()->willReturn(RouteInterface::TYPE_PATTERN);
        $this->route->matchesRequestTarget(Argument::cetera())->willReturn(true);
        $this->route->getPathVariables()->willReturn($variables);

        $this->router->register("GET", $target, "middleware");
        $this->router->__invoke($this->request, $this->response, $this->next);

        $isRequestWithExpectedAttribute = function ($request) use ($name, $value) {
            return $request->getAttribute($name) === $value;
        };

        $this->route->__invoke(
            Argument::that($isRequestWithExpectedAttribute),
            Argument::cetera()
        )->shouldHaveBeenCalled();
    }

    public function pathVariableProvider()
    {
        return [
            ["id", "1024"],
            ["name", "Molly"]
        ];
    }

    public function testSetPathVariablesAttributeAsArray()
    {
        $attributeName = "pathVariables";

        $target = "/";
        $variables = [
            "id" => "1024",
            "name" => "Molly"
        ];

        $this->request = $this->request->withRequestTarget($target);

        $this->route->getTarget()->willReturn($target);
        $this->route->getType()->willReturn(RouteInterface::TYPE_PATTERN);
        $this->route->matchesRequestTarget(Argument::cetera())->willReturn(true);
        $this->route->getPathVariables()->willReturn($variables);

        $this->router->__construct(new Dispatcher(), $attributeName);
        $this->router->register("GET", $target, "middleware");
        $this->router->__invoke($this->request, $this->response, $this->next);

        $isRequestWithExpectedAttribute = function ($request) use ($attributeName, $variables) {
            return $request->getAttribute($attributeName) === $variables;
        };

        $this->route->__invoke(
            Argument::that($isRequestWithExpectedAttribute),
            Argument::cetera()
        )->shouldHaveBeenCalled();
    }

    // ------------------------------------------------------------------------
    // No Matching Routes

    public function testResponds404WhenNoRouteMatches()
    {
        $this->request = $this->request->withRequestTarget("/no/match");
        $response = $this->router->__invoke($this->request, $this->response, $this->next);
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testStopsPropagatingWhenNoRouteMatches()
    {
        $this->request = $this->request->withRequestTarget("/no/match");
        $this->router->__invoke($this->request, $this->response, $this->next);
        $this->assertFalse($this->next->called);
    }

    public function testRegisterIsFluid()
    {
        $router = $this->router
            ->register("GET", "/", "middleware")
            ->register("POST", "/", "middleware");
        $this->assertSame($this->router, $router);
    }
}
