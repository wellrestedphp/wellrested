<?php

declare(strict_types=1);

namespace WellRESTed\Routing;

use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ProphecyInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WellRESTed\Message\Response;
use WellRESTed\Message\ServerRequest;
use WellRESTed\Server;
use WellRESTed\Test\Doubles\HandlerDouble;
use WellRESTed\Test\Doubles\MiddlewareDouble;
use WellRESTed\Test\Doubles\NextDouble;
use WellRESTed\Test\TestCase;

class RouterTest extends TestCase
{
    use ProphecyTrait;

    private Server $server;
    private ProphecyInterface $routeMap;
    private ServerRequestInterface $request;
    private ResponseInterface $response;
    private Router $router;
    private NextDouble $next;

    protected function setUp(): void
    {
        parent::setUp();

        $this->server = new Server();
        $this->router = $this->server->createRouter();
        $this->request = new ServerRequest();
        $this->response = new Response();
        $this->next = new NextDouble();
    }

    /**
     * Run a request through the class under test and return the response.
     *
     * @return ResponseInterface
     */
    private function dispatch(): ResponseInterface
    {
        return call_user_func(
            $this->router,
            $this->request,
            $this->response,
            $this->next
        );
    }

    // -------------------------------------------------------------------------

    public function testWhenRequestMatchesRouteDispatchesRoute(): void
    {
        // Arrange
        $handler = new MiddlewareDouble();
        $this->router->register('GET', '/cats/', $handler);

        // Act
        $this->request = $this->request->withRequestTarget('/cats/');
        $this->dispatch();

        // Assert
        $this->assertTrue($handler->called);
    }

    public function testAddsPathVariablesAsRequestAttributes(): void
    {
        // Arrange
        $handler = new MiddlewareDouble();
        $this->router->register('GET', '/pets/{type}/{name}', $handler);

        // Act
        $this->request = $this->request->withRequestTarget('/pets/cats/molly');
        $this->dispatch();

        // Assert
        $this->assertTrue($handler->called);
        $this->assertEquals('cats', $handler->request->getAttribute('type'));
        $this->assertEquals('molly', $handler->request->getAttribute('name'));
    }

    public function testAddsPathVariablesAsSingleArrayAttributeWhenConfigured(): void
    {
        // Arrange
        $handler = new MiddlewareDouble();
        $this->router->register('GET', '/pets/{type}/{name}', $handler);
        $this->server->setPathVariablesAttributeName('pathVars');

        // Act
        $this->request = $this->request->withRequestTarget('/pets/cats/molly');
        $this->dispatch();

        // Assert
        $this->assertTrue($handler->called);
        $pathVars = $handler->request->getAttribute('pathVars');
        $this->assertEquals('cats', $pathVars['type']);
        $this->assertEquals('molly', $pathVars['name']);
    }

    public function testRunsMiddlewareWhenRouteMatchesRequest(): void
    {
        // Arrange
        $handler = new MiddlewareDouble();
        $middleware1 = new MiddlewareDouble();
        $middleware2 = new MiddlewareDouble();
        $this->router->register('GET', '/cats/', $handler);
        $this->router->add($middleware1);
        $this->router->add($middleware2);

        // Act
        $this->request = $this->request->withRequestTarget('/cats/');
        $this->dispatch();

        // Assert
        $this->assertTrue($middleware1->called);
        $this->assertTrue($middleware2->called);
        $this->assertTrue($handler->called);
    }

    public function testDoesNotRunMiddlewareWhenNoRouteMatches(): void
    {
        // Arrange
        $middleware = new MiddlewareDouble();
        $this->router->add($middleware);

        // Act
        $this->request = $this->request->withRequestTarget('/cats/');
        $this->dispatch();

        // Assert
        $this->assertFalse($middleware->called);
    }

    public function testWhenNoRouteMatchesByDefaultResponds404(): void
    {
        // Act
        $response = $this->dispatch();

        // Assert
        $this->assertEquals(404, $response->getStatusCode());
    }

    // -------------------------------------------------------------------------
    // Trailing Shash

    /** @dataProvider trailingSlashProvider */
    public function testRequestReturnsExpectedResponse(
        int $expected,
        string $route,
        string $target,
        TrailingSlashMode $mode,
        string $location = ''
    ): void {
        // Arrange
        $handler = new HandlerDouble(new Response(200));
        $this->router->register('GET', $route, $handler);
        $this->server->setTrailingSlashMode($mode);

        // Act
        $this->request = $this->request->withRequestTarget($target);
        $response = $this->dispatch();

        // Assert
        $this->assertEquals($expected, $response->getStatusCode());
        $this->assertEquals($location, $response->getHeaderLine('Location'));
    }

    public function trailingSlashProvider(): array
    {
        return [
            'Strict: missing slash'    => [404, '/path/', '/path',       TrailingSlashMode::STRICT],
            'Strict: extra slash'      => [404, '/path',  '/path/',      TrailingSlashMode::STRICT],
            'Strict: exact match'      => [200, '/path/', '/path/',      TrailingSlashMode::STRICT],
            'Strict: no match'         => [404, '/path/', '/nope',       TrailingSlashMode::STRICT],

            'Loose: missing slash'     => [200, '/path/', '/path',       TrailingSlashMode::LOOSE],
            'Loose: extra slash'       => [200, '/path',  '/path/',      TrailingSlashMode::LOOSE],
            'Loose: exact match'       => [200, '/path/', '/path/',      TrailingSlashMode::LOOSE],
            'Losse: no match'          => [404, '/path/', '/nope',       TrailingSlashMode::LOOSE],

            'Redirect: missing slash'  => [301, '/path/', '/path',       TrailingSlashMode::REDIRECT, '/path/'],
            'Redirect: extra slash'    => [301, '/path',  '/path/',      TrailingSlashMode::REDIRECT, '/path'],
            'Redirect: exact match'    => [200, '/path/', '/path/',      TrailingSlashMode::REDIRECT],
            'Redirect: no match'       => [404, '/path/', '/nope',       TrailingSlashMode::REDIRECT],
            'Redirect: no match slash' => [404, '/path/', '/nope/',      TrailingSlashMode::REDIRECT],
            'Redirect: query'          => [301, '/path/', '/path?query', TrailingSlashMode::REDIRECT, '/path/?query']
        ];
    }

    public function testWhenNoRouteMatchesByDefaultDoesNotPropagatesToNextMiddleware(): void
    {
        // Act
        $this->dispatch();

        // Assert
        $this->assertFalse($this->next->called);
    }

    public function testWhenNoRouteMatchesAndContinueModePropagatesToNextMiddleware(): void
    {
        // Arrange
        $this->router->continueOnNotFound();

        // Act
        $this->dispatch();

        // Assert
        $this->assertTrue($this->next->called);
    }

    public function testReturnsArrayOfMiddleware(): void
    {
        // Arrange
        $middleware1 = new MiddlewareDouble();
        $middleware2 = new MiddlewareDouble();
        $middleware3 = new MiddlewareDouble();

        $this->router
            ->add($middleware1)
            ->add($middleware2)
            ->add($middleware3);

        // Act
        $middleware = $this->router->getMiddleware();

        // Assert
        $this->assertEquals([$middleware1, $middleware2, $middleware3], $middleware);
    }

    public function testReturnsMapOfRoutesByTarget(): void
    {
        // Arrange
        $this->router->register('GET', '/cats/', new MiddlewareDouble());
        $this->router->register('POST', '/cats/', new MiddlewareDouble());
        $this->router->register('GET', '/cats/{id}', new MiddlewareDouble());
        $this->router->register('PUT', '/cats/{id}', new MiddlewareDouble());
        $this->router->register('GET', '/dogs/', new MiddlewareDouble());
        $this->router->register('GET', '/dogs/{id}', new MiddlewareDouble());

        // Act
        $routes = $this->router->getRoutes();

        // Assert
        $this->assertArrayHasKey('/cats/', $routes);
        $this->assertArrayHasKey('/cats/{id}', $routes);
        $this->assertArrayHasKey('/dogs/', $routes);
        $this->assertArrayHasKey('/dogs/{id}', $routes);
    }
}
