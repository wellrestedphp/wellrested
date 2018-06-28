<?php

namespace WellRESTed\Test\Unit\Routing;

use Prophecy\Argument;
use WellRESTed\Dispatching\Dispatcher;
use WellRESTed\Message\Response;
use WellRESTed\Message\ServerRequest;
use WellRESTed\Routing\Route\RouteFactory;
use WellRESTed\Routing\Route\RouteInterface;
use WellRESTed\Routing\Router;
use WellRESTed\Test\Doubles\NextMock;
use WellRESTed\Test\TestCase;

class RouterTest extends TestCase
{
    private $factory;
    private $request;
    private $response;
    private $route;
    private $router;
    private $next;

    public function setUp()
    {
        parent::setUp();

        $this->route = $this->prophesize(RouteInterface::class);
        $this->route->__invoke(Argument::cetera())->willReturn(new Response());
        $this->route->register(Argument::cetera())->willReturn();
        $this->route->getType()->willReturn(RouteInterface::TYPE_STATIC);
        $this->route->getTarget()->willReturn("/");
        $this->route->getPathVariables()->willReturn([]);

        $this->factory = $this->prophesize(RouteFactory::class);
        $this->factory->create(Argument::any())
            ->willReturn($this->route->reveal());

        RouterWithFactory::$routeFactory = $this->factory->reveal();

        $this->router = new RouterWithFactory();

        $this->request = new ServerRequest();
        $this->response = new Response();
        $this->next = new NextMock();
    }

    // ------------------------------------------------------------------------
    // Construction

    public function testCreatesInstance()
    {
        $router = new Router();
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

    public function testPassesMethodAndMiddlewareToRoute()
    {
        $this->router->register("GET", "/", "middleware");

        $this->route->register("GET", "middleware")->shouldHaveBeenCalled();
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

        $this->route->__invoke(Argument::cetera())
            ->shouldHaveBeenCalled();
    }

    public function testDispatchesPrefixRoute()
    {
        $target = "/animals/cats/*";
        $this->request = $this->request->withRequestTarget("/animals/cats/molly");

        $this->route->getTarget()->willReturn($target);
        $this->route->getType()->willReturn(RouteInterface::TYPE_PREFIX);

        $this->router->register("GET", $target, "middleware");
        $this->router->__invoke($this->request, $this->response, $this->next);

        $this->route->__invoke(Argument::cetera())
            ->shouldHaveBeenCalled();
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

        $this->route->__invoke(Argument::cetera())
            ->shouldHaveBeenCalled();
    }

    public function testDispatchesStaticRouteBeforePrefixRoute()
    {
        $staticRoute = $this->prophesize(RouteInterface::class);
        $staticRoute->register(Argument::cetera())->willReturn();
        $staticRoute->getTarget()->willReturn("/cats/");
        $staticRoute->getType()->willReturn(RouteInterface::TYPE_STATIC);
        $staticRoute->__invoke(Argument::cetera())->willReturn(new Response());

        $prefixRoute = $this->prophesize(RouteInterface::class);
        $prefixRoute->register(Argument::cetera())->willReturn();
        $prefixRoute->getTarget()->willReturn("/cats/*");
        $prefixRoute->getType()->willReturn(RouteInterface::TYPE_PREFIX);
        $prefixRoute->__invoke(Argument::cetera())->willReturn(new Response());

        $this->request = $this->request->withRequestTarget("/cats/");

        $this->factory->create("/cats/")->willReturn($staticRoute->reveal());
        $this->factory->create("/cats/*")->willReturn($prefixRoute->reveal());

        $this->router->register("GET", "/cats/", "middleware");
        $this->router->register("GET", "/cats/*", "middleware");
        $this->router->__invoke($this->request, $this->response, $this->next);

        $staticRoute->__invoke(Argument::cetera())
            ->shouldHaveBeenCalled();
    }

    public function testDispatchesLongestMatchingPrefixRoute()
    {
        // Note: The longest route is also good for 2 points in Settlers of Catan.

        $shortRoute = $this->prophesize(RouteInterface::class);
        $shortRoute->register(Argument::cetera())->willReturn();
        $shortRoute->getTarget()->willReturn("/animals/*");
        $shortRoute->getType()->willReturn(RouteInterface::TYPE_PREFIX);
        $shortRoute->__invoke(Argument::cetera())->willReturn(new Response());

        $longRoute = $this->prophesize(RouteInterface::class);
        $longRoute->register(Argument::cetera())->willReturn();
        $longRoute->getTarget()->willReturn("/animals/cats/*");
        $longRoute->getType()->willReturn(RouteInterface::TYPE_PREFIX);
        $longRoute->__invoke(Argument::cetera())->willReturn(new Response());

        $this->request = $this->request
            ->withRequestTarget("/animals/cats/molly");

        $this->factory->create("/animals/*")->willReturn($shortRoute->reveal());
        $this->factory->create("/animals/cats/*")->willReturn($longRoute->reveal());

        $this->router->register("GET", "/animals/*", "middleware");
        $this->router->register("GET", "/animals/cats/*", "middleware");
        $this->router->__invoke($this->request, $this->response, $this->next);

        $longRoute->__invoke(Argument::cetera())
            ->shouldHaveBeenCalled();
    }

    public function testDispatchesPrefixRouteBeforePatternRoute()
    {
        $prefixRoute = $this->prophesize(RouteInterface::class);
        $prefixRoute->register(Argument::cetera())->willReturn();
        $prefixRoute->getTarget()->willReturn("/cats/*");
        $prefixRoute->getType()->willReturn(RouteInterface::TYPE_PREFIX);
        $prefixRoute->__invoke(Argument::cetera())->willReturn(new Response());

        $patternRoute = $this->prophesize(RouteInterface::class);
        $patternRoute->register(Argument::cetera())->willReturn();
        $patternRoute->getTarget()->willReturn("/cats/{id}");
        $patternRoute->getType()->willReturn(RouteInterface::TYPE_PATTERN);
        $patternRoute->__invoke(Argument::cetera())->willReturn(new Response());

        $this->request = $this->request->withRequestTarget("/cats/");

        $this->factory->create("/cats/*")->willReturn($prefixRoute->reveal());
        $this->factory->create("/cats/{id}")->willReturn($patternRoute->reveal());

        $this->router->register("GET", "/cats/*", "middleware");
        $this->router->register("GET", "/cats/{id}", "middleware");
        $this->router->__invoke($this->request, $this->response, $this->next);

        $prefixRoute->__invoke(Argument::cetera())
            ->shouldHaveBeenCalled();
    }

    public function testDispatchesFirstMatchingPatternRoute()
    {
        $patternRoute1 = $this->prophesize(RouteInterface::class);
        $patternRoute1->register(Argument::cetera())->willReturn();
        $patternRoute1->getTarget()->willReturn("/cats/{id}");
        $patternRoute1->getType()->willReturn(RouteInterface::TYPE_PATTERN);
        $patternRoute1->getPathVariables()->willReturn([]);
        $patternRoute1->matchesRequestTarget(Argument::any())->willReturn(true);
        $patternRoute1->__invoke(Argument::cetera())->willReturn(new Response());

        $patternRoute2 = $this->prophesize(RouteInterface::class);
        $patternRoute2->register(Argument::cetera())->willReturn();
        $patternRoute2->getTarget()->willReturn("/cats/{name}");
        $patternRoute2->getType()->willReturn(RouteInterface::TYPE_PATTERN);
        $patternRoute2->getPathVariables()->willReturn([]);
        $patternRoute2->matchesRequestTarget(Argument::any())->willReturn(true);
        $patternRoute2->__invoke(Argument::cetera())->willReturn(new Response());

        $this->request = $this->request->withRequestTarget("/cats/molly");

        $this->factory->create("/cats/{id}")->willReturn($patternRoute1->reveal());
        $this->factory->create("/cats/{name}")->willReturn($patternRoute2->reveal());

        $this->router->register("GET", "/cats/{id}", "middleware");
        $this->router->register("GET", "/cats/{name}", "middleware");
        $this->router->__invoke($this->request, $this->response, $this->next);

        $patternRoute1->__invoke(Argument::cetera())
            ->shouldHaveBeenCalled();
    }

    public function testStopsTestingPatternsAfterFirstSuccessfulMatch()
    {
        $patternRoute1 = $this->prophesize(RouteInterface::class);
        $patternRoute1->register(Argument::cetera())->willReturn();
        $patternRoute1->getTarget()->willReturn("/cats/{id}");
        $patternRoute1->getType()->willReturn(RouteInterface::TYPE_PATTERN);
        $patternRoute1->getPathVariables()->willReturn([]);
        $patternRoute1->matchesRequestTarget(Argument::any())->willReturn(true);
        $patternRoute1->__invoke(Argument::cetera())->willReturn(new Response());

        $patternRoute2 = $this->prophesize(RouteInterface::class);
        $patternRoute2->register(Argument::cetera())->willReturn();
        $patternRoute2->getTarget()->willReturn("/cats/{name}");
        $patternRoute2->getType()->willReturn(RouteInterface::TYPE_PATTERN);
        $patternRoute2->getPathVariables()->willReturn([]);
        $patternRoute2->matchesRequestTarget(Argument::any())->willReturn(true);
        $patternRoute2->__invoke(Argument::cetera())->willReturn(new Response());

        $this->request = $this->request->withRequestTarget("/cats/molly");

        $this->factory->create("/cats/{id}")->willReturn($patternRoute1->reveal());
        $this->factory->create("/cats/{name}")->willReturn($patternRoute2->reveal());

        $this->router->register("GET", "/cats/{id}", "middleware");
        $this->router->register("GET", "/cats/{name}", "middleware");
        $this->router->__invoke($this->request, $this->response, $this->next);

        $patternRoute2->matchesRequestTarget(Argument::any())
            ->shouldNotHaveBeenCalled();
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
    // No Match

    public function testWhenNoRouteMatchesByDefaultResponds404()
    {
        $this->request = $this->request->withRequestTarget("/no/match");
        $response = $this->router->__invoke($this->request, $this->response, $this->next);
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testWhenNoRouteMatchesByDefaultDoesNotPropagatesToNextMiddleware()
    {
        $this->request = $this->request->withRequestTarget("/no/match");
        $this->router->__invoke($this->request, $this->response, $this->next);
        $this->assertFalse($this->next->called);
    }

    public function testWhenNoRouteMatchesAndContinueModePropagatesToNextMiddleware()
    {
        $this->request = $this->request->withRequestTarget("/no/match");
        $this->router->continue();
        $this->router->__invoke($this->request, $this->response, $this->next);
        $this->assertTrue($this->next->called);
    }

    // ------------------------------------------------------------------------
    // Middleware for Routes

    public function testCallsRouterMiddlewareBeforeRouteMiddleware()
    {
        $middlewareRequest = new ServerRequest();
        $middlewareResponse = new Response();

        $middleware = function ($rqst, $resp, $next) use (
            $middlewareRequest,
            $middlewareResponse
        ) {
            return $next($middlewareRequest, $middlewareResponse);
        };

        $this->router->addMiddleware($middleware);
        $this->router->register("GET", "/", "Handler");

        $this->router->__invoke($this->request, $this->response, $this->next);

        $this->route->__invoke(
            $middlewareRequest,
            $middlewareResponse,
            Argument::any())->shouldHaveBeenCalled();
    }

    public function testDoesNotCallRouterMiddlewareWhenNoRouteMatches()
    {
        $middlewareCalled = false;
        $middlewareRequest = new ServerRequest();
        $middlewareResponse = new Response();

        $middleware = function ($rqst, $resp, $next) use (
            $middlewareRequest,
            $middlewareResponse,
            &$middlewareCalled
        ) {
            $middlewareCalled = true;
            return $next($middlewareRequest, $middlewareResponse);
        };

        $this->request = $this->request->withRequestTarget("/no/match");

        $this->router->addMiddleware($middleware);
        $this->router->register("GET", "/", "Handler");

        $this->router->__invoke($this->request, $this->response, $this->next);

        $this->assertFalse($middlewareCalled);
    }
}

// -----------------------------------------------------------------------------

class RouterWithFactory extends Router
{
    static $routeFactory;

    protected function getRouteFactory($dispatcher)
    {
        return self::$routeFactory;
    }
}
