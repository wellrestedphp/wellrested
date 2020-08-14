<?php

namespace WellRESTed\Routing;

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use WellRESTed\Dispatching\Dispatcher;
use WellRESTed\Dispatching\DispatcherInterface;
use WellRESTed\Message\Response;
use WellRESTed\Message\ServerRequest;
use WellRESTed\Routing\Route\Route;
use WellRESTed\Routing\Route\RouteFactory;
use WellRESTed\Test\Doubles\NextMock;
use WellRESTed\Test\TestCase;

class RouterTest extends TestCase
{
    use ProphecyTrait;

    private $factory;
    private $request;
    private $response;
    private $route;
    private $router;
    private $next;

    protected function setUp(): void
    {
        parent::setUp();

        $this->route = $this->prophesize(Route::class);
        $this->route->__invoke(Argument::cetera())->willReturn(new Response());
        $this->route->register(Argument::cetera());
        $this->route->getType()->willReturn(Route::TYPE_STATIC);
        $this->route->getTarget()->willReturn('/');
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

    // -------------------------------------------------------------------------
    // Construction

    public function testCreatesInstance(): void
    {
        $router = new Router();
        $this->assertNotNull($router);
    }

    // -------------------------------------------------------------------------
    // Populating

    public function testCreatesRouteForTarget(): void
    {
        $this->router->register('GET', '/', 'middleware');

        $this->factory->create('/')->shouldHaveBeenCalled();
    }

    public function testDoesNotRecreateRouteForExistingTarget(): void
    {
        $this->router->register('GET', '/', 'middleware');
        $this->router->register('POST', '/', 'middleware');

        $this->factory->create('/')->shouldHaveBeenCalledTimes(1);
    }

    public function testPassesMethodAndMiddlewareToRoute(): void
    {
        $this->router->register('GET', '/', 'middleware');

        $this->route->register('GET', 'middleware')->shouldHaveBeenCalled();
    }

    // -------------------------------------------------------------------------
    // Dispatching

    public function testDispatchesStaticRoute(): void
    {
        $target = '/';
        $this->request = $this->request->withRequestTarget($target);

        $this->route->getTarget()->willReturn($target);
        $this->route->getType()->willReturn(Route::TYPE_STATIC);

        $this->router->register('GET', $target, 'middleware');
        $this->router->__invoke($this->request, $this->response, $this->next);

        $this->route->__invoke(Argument::cetera())
            ->shouldHaveBeenCalled();
    }

    public function testDispatchesPrefixRoute(): void
    {
        $target = '/animals/cats/*';
        $this->request = $this->request->withRequestTarget('/animals/cats/molly');

        $this->route->getTarget()->willReturn($target);
        $this->route->getType()->willReturn(Route::TYPE_PREFIX);

        $this->router->register('GET', $target, 'middleware');
        $this->router->__invoke($this->request, $this->response, $this->next);

        $this->route->__invoke(Argument::cetera())
            ->shouldHaveBeenCalled();
    }

    public function testDispatchesPatternRoute(): void
    {
        $target = '/';
        $this->request = $this->request->withRequestTarget($target);

        $this->route->getTarget()->willReturn($target);
        $this->route->getType()->willReturn(Route::TYPE_PATTERN);
        $this->route->matchesRequestTarget(Argument::cetera())->willReturn(true);

        $this->router->register('GET', $target, 'middleware');
        $this->router->__invoke($this->request, $this->response, $this->next);

        $this->route->__invoke(Argument::cetera())
            ->shouldHaveBeenCalled();
    }

    public function testDispatchesStaticRouteBeforePrefixRoute(): void
    {
        $staticRoute = $this->prophesize(Route::class);
        $staticRoute->register(Argument::cetera());
        $staticRoute->getTarget()->willReturn('/cats/');
        $staticRoute->getType()->willReturn(Route::TYPE_STATIC);
        $staticRoute->__invoke(Argument::cetera())->willReturn(new Response());

        $prefixRoute = $this->prophesize(Route::class);
        $prefixRoute->register(Argument::cetera());
        $prefixRoute->getTarget()->willReturn('/cats/*');
        $prefixRoute->getType()->willReturn(Route::TYPE_PREFIX);
        $prefixRoute->__invoke(Argument::cetera())->willReturn(new Response());

        $this->request = $this->request->withRequestTarget('/cats/');

        $this->factory->create('/cats/')->willReturn($staticRoute->reveal());
        $this->factory->create('/cats/*')->willReturn($prefixRoute->reveal());

        $this->router->register('GET', '/cats/', 'middleware');
        $this->router->register('GET', '/cats/*', 'middleware');
        $this->router->__invoke($this->request, $this->response, $this->next);

        $staticRoute->__invoke(Argument::cetera())
            ->shouldHaveBeenCalled();
    }

    public function testDispatchesLongestMatchingPrefixRoute(): void
    {
        // Note: The longest route is also good for 2 points in Settlers of Catan.

        $shortRoute = $this->prophesize(Route::class);
        $shortRoute->register(Argument::cetera());
        $shortRoute->getTarget()->willReturn('/animals/*');
        $shortRoute->getType()->willReturn(Route::TYPE_PREFIX);
        $shortRoute->__invoke(Argument::cetera())->willReturn(new Response());

        $longRoute = $this->prophesize(Route::class);
        $longRoute->register(Argument::cetera());
        $longRoute->getTarget()->willReturn('/animals/cats/*');
        $longRoute->getType()->willReturn(Route::TYPE_PREFIX);
        $longRoute->__invoke(Argument::cetera())->willReturn(new Response());

        $this->request = $this->request
            ->withRequestTarget('/animals/cats/molly');

        $this->factory->create('/animals/*')->willReturn($shortRoute->reveal());
        $this->factory->create('/animals/cats/*')->willReturn($longRoute->reveal());

        $this->router->register('GET', '/animals/*', 'middleware');
        $this->router->register('GET', '/animals/cats/*', 'middleware');
        $this->router->__invoke($this->request, $this->response, $this->next);

        $longRoute->__invoke(Argument::cetera())
            ->shouldHaveBeenCalled();
    }

    public function testDispatchesPrefixRouteBeforePatternRoute(): void
    {
        $prefixRoute = $this->prophesize(Route::class);
        $prefixRoute->register(Argument::cetera());
        $prefixRoute->getTarget()->willReturn('/cats/*');
        $prefixRoute->getType()->willReturn(Route::TYPE_PREFIX);
        $prefixRoute->__invoke(Argument::cetera())->willReturn(new Response());

        $patternRoute = $this->prophesize(Route::class);
        $patternRoute->register(Argument::cetera());
        $patternRoute->getTarget()->willReturn('/cats/{id}');
        $patternRoute->getType()->willReturn(Route::TYPE_PATTERN);
        $patternRoute->__invoke(Argument::cetera())->willReturn(new Response());

        $this->request = $this->request->withRequestTarget('/cats/');

        $this->factory->create('/cats/*')->willReturn($prefixRoute->reveal());
        $this->factory->create('/cats/{id}')->willReturn($patternRoute->reveal());

        $this->router->register('GET', '/cats/*', 'middleware');
        $this->router->register('GET', '/cats/{id}', 'middleware');
        $this->router->__invoke($this->request, $this->response, $this->next);

        $prefixRoute->__invoke(Argument::cetera())
            ->shouldHaveBeenCalled();
    }

    public function testDispatchesFirstMatchingPatternRoute(): void
    {
        $patternRoute1 = $this->prophesize(Route::class);
        $patternRoute1->register(Argument::cetera());
        $patternRoute1->getTarget()->willReturn('/cats/{id}');
        $patternRoute1->getType()->willReturn(Route::TYPE_PATTERN);
        $patternRoute1->getPathVariables()->willReturn([]);
        $patternRoute1->matchesRequestTarget(Argument::any())->willReturn(true);
        $patternRoute1->__invoke(Argument::cetera())->willReturn(new Response());

        $patternRoute2 = $this->prophesize(Route::class);
        $patternRoute2->register(Argument::cetera());
        $patternRoute2->getTarget()->willReturn('/cats/{name}');
        $patternRoute2->getType()->willReturn(Route::TYPE_PATTERN);
        $patternRoute2->getPathVariables()->willReturn([]);
        $patternRoute2->matchesRequestTarget(Argument::any())->willReturn(true);
        $patternRoute2->__invoke(Argument::cetera())->willReturn(new Response());

        $this->request = $this->request->withRequestTarget('/cats/molly');

        $this->factory->create('/cats/{id}')->willReturn($patternRoute1->reveal());
        $this->factory->create('/cats/{name}')->willReturn($patternRoute2->reveal());

        $this->router->register('GET', '/cats/{id}', 'middleware');
        $this->router->register('GET', '/cats/{name}', 'middleware');
        $this->router->__invoke($this->request, $this->response, $this->next);

        $patternRoute1->__invoke(Argument::cetera())
            ->shouldHaveBeenCalled();
    }

    public function testStopsTestingPatternsAfterFirstSuccessfulMatch(): void
    {
        $patternRoute1 = $this->prophesize(Route::class);
        $patternRoute1->register(Argument::cetera());
        $patternRoute1->getTarget()->willReturn('/cats/{id}');
        $patternRoute1->getType()->willReturn(Route::TYPE_PATTERN);
        $patternRoute1->getPathVariables()->willReturn([]);
        $patternRoute1->matchesRequestTarget(Argument::any())->willReturn(true);
        $patternRoute1->__invoke(Argument::cetera())->willReturn(new Response());

        $patternRoute2 = $this->prophesize(Route::class);
        $patternRoute2->register(Argument::cetera());
        $patternRoute2->getTarget()->willReturn('/cats/{name}');
        $patternRoute2->getType()->willReturn(Route::TYPE_PATTERN);
        $patternRoute2->getPathVariables()->willReturn([]);
        $patternRoute2->matchesRequestTarget(Argument::any())->willReturn(true);
        $patternRoute2->__invoke(Argument::cetera())->willReturn(new Response());

        $this->request = $this->request->withRequestTarget('/cats/molly');

        $this->factory->create('/cats/{id}')->willReturn($patternRoute1->reveal());
        $this->factory->create('/cats/{name}')->willReturn($patternRoute2->reveal());

        $this->router->register('GET', '/cats/{id}', 'middleware');
        $this->router->register('GET', '/cats/{name}', 'middleware');
        $this->router->__invoke($this->request, $this->response, $this->next);

        $patternRoute2->matchesRequestTarget(Argument::any())
            ->shouldNotHaveBeenCalled();
    }

    public function testMatchesPathAgainstRouteWithoutQuery(): void
    {
        $target = '/my/path?cat=molly&dog=bear';

        $this->request = $this->request->withRequestTarget($target);

        $this->route->getTarget()->willReturn($target);
        $this->route->getType()->willReturn(Route::TYPE_PATTERN);
        $this->route->matchesRequestTarget(Argument::cetera())->willReturn(true);

        $this->router->register('GET', $target, 'middleware');
        $this->router->__invoke($this->request, $this->response, $this->next);

        $this->route->matchesRequestTarget('/my/path')->shouldHaveBeenCalled();
    }

    // -------------------------------------------------------------------------
    // Path Variables

    /**
     * @dataProvider pathVariableProvider
     * @param string $name
     * @param string $value
     */
    public function testSetPathVariablesAttributeIndividually(string $name, string $value): void
    {
        $target = '/';
        $variables = [
            'id' => '1024',
            'name' => 'Molly'
        ];

        $this->request = $this->request->withRequestTarget($target);

        $this->route->getTarget()->willReturn($target);
        $this->route->getType()->willReturn(Route::TYPE_PATTERN);
        $this->route->matchesRequestTarget(Argument::cetera())->willReturn(true);
        $this->route->getPathVariables()->willReturn($variables);

        $this->router->register('GET', $target, 'middleware');
        $this->router->__invoke($this->request, $this->response, $this->next);

        $isRequestWithExpectedAttribute = function ($request) use ($name, $value) {
            return $request->getAttribute($name) === $value;
        };

        $this->route->__invoke(
            Argument::that($isRequestWithExpectedAttribute),
            Argument::cetera()
        )->shouldHaveBeenCalled();
    }

    public function pathVariableProvider(): array
    {
        return [
            ['id', '1024'],
            ['name', 'Molly']
        ];
    }

    public function testSetPathVariablesAttributeAsArray(): void
    {
        $attributeName = 'pathVariables';

        $target = '/';
        $variables = [
            'id' => '1024',
            'name' => 'Molly'
        ];

        $this->request = $this->request->withRequestTarget($target);

        $this->route->getTarget()->willReturn($target);
        $this->route->getType()->willReturn(Route::TYPE_PATTERN);
        $this->route->matchesRequestTarget(Argument::cetera())->willReturn(true);
        $this->route->getPathVariables()->willReturn($variables);

        $this->router->__construct(new Dispatcher(), $attributeName);
        $this->router->register('GET', $target, 'middleware');
        $this->router->__invoke($this->request, $this->response, $this->next);

        $isRequestWithExpectedAttribute = function ($request) use ($attributeName, $variables) {
            return $request->getAttribute($attributeName) === $variables;
        };

        $this->route->__invoke(
            Argument::that($isRequestWithExpectedAttribute),
            Argument::cetera()
        )->shouldHaveBeenCalled();
    }

    // -------------------------------------------------------------------------
    // No Match

    public function testWhenNoRouteMatchesByDefaultResponds404(): void
    {
        $this->request = $this->request->withRequestTarget('/no/match');
        $response = $this->router->__invoke($this->request, $this->response, $this->next);
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testWhenNoRouteMatchesByDefaultDoesNotPropagatesToNextMiddleware(): void
    {
        $this->request = $this->request->withRequestTarget('/no/match');
        $this->router->__invoke($this->request, $this->response, $this->next);
        $this->assertFalse($this->next->called);
    }

    public function testWhenNoRouteMatchesAndContinueModePropagatesToNextMiddleware(): void
    {
        $this->request = $this->request->withRequestTarget('/no/match');
        $this->router->continueOnNotFound();
        $this->router->__invoke($this->request, $this->response, $this->next);
        $this->assertTrue($this->next->called);
    }

    // -------------------------------------------------------------------------
    // Middleware for Routes

    public function testCallsRouterMiddlewareBeforeRouteMiddleware(): void
    {
        $middlewareRequest = new ServerRequest();
        $middlewareResponse = new Response();

        $middleware = function ($rqst, $resp, $next) use (
            $middlewareRequest,
            $middlewareResponse
        ) {
            return $next($middlewareRequest, $middlewareResponse);
        };

        $this->router->add($middleware);
        $this->router->register('GET', '/', 'Handler');

        $this->router->__invoke($this->request, $this->response, $this->next);

        $this->route->__invoke(
            $middlewareRequest,
            $middlewareResponse,
            Argument::any()
        )->shouldHaveBeenCalled();
    }

    public function testDoesNotCallRouterMiddlewareWhenNoRouteMatches(): void
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

        $this->request = $this->request->withRequestTarget('/no/match');

        $this->router->add($middleware);
        $this->router->register('GET', '/', 'Handler');

        $this->router->__invoke($this->request, $this->response, $this->next);

        $this->assertFalse($middlewareCalled);
    }
}

// -----------------------------------------------------------------------------

class RouterWithFactory extends Router
{
    public static $routeFactory;

    protected function getRouteFactory(DispatcherInterface $dispatcher): RouteFactory
    {
        return self::$routeFactory;
    }
}
