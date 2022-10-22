<?php

declare(strict_types=1);

namespace WellRESTed\Routing\Route;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WellRESTed\Message\Response;
use WellRESTed\Message\ServerRequest;
use WellRESTed\Server;
use WellRESTed\Test\Doubles\NextMock;
use WellRESTed\Test\TestCase;

class RouteMapTest extends TestCase
{
    private Server $server;
    private ServerRequestInterface $request;
    private ResponseInterface $response;
    private NextMock $next;
    private RouteMap $routeMap;

    protected function setUp(): void
    {
        parent::setUp();

        $this->server = new Server();
        $this->routeMap = new RouteMap($this->server);
        $this->request = new ServerRequest();
        $this->response = new Response();
        $this->next = new NextMock();
    }

    /**
     * Run a request through the class under test and return the response.
     */
    private function dispatch($dispatchable): ResponseInterface
    {
        return call_user_func(
            $dispatchable,
            $this->request,
            $this->response,
            $this->next
        );
    }

    // -------------------------------------------------------------------------
    // Locating Route

    /** @dataProvider singleRouteProvider */
    public function testSingleRoute(string $target, string $requestTarget): void
    {
        // Arrange
        $dispatchable = new NextMock();
        $this->routeMap->register('GET', $target, $dispatchable);
        $this->request = $this->request->withRequestTarget($requestTarget);

        // Act
        $route = $this->routeMap->getRoute($this->request);

        // Assert
        $this->assertNotNull($route);
        $this->dispatch($route);
        $this->assertTrue($dispatchable->called);
    }

    public function singleRouteProvider(): array
    {
        return [
            'Static' => ['/', '/'],
            'Prefix' => ['/cats/*', '/cats/molly'],
            'Pattern' => ['/cats/{id}', '/cats/molly'],
            'Query' => ['/pets/', '/pets/?cat=molly&dog=bear'],
            'Empty Segement' => ['//extra/slash', '//extra/slash']
        ];
    }

    /** @dataProvider multipleRouteProvider */
    public function testMultipleRoutes(
        string $requestTarget,
        string $primaryTarget,
        string $secondaryTarget
    ): void {
        // Regsiter two handlers. Expect the request to match the "primary".

        // Arrange
        $primaryHandler = new NextMock();
        $secondaryHandler = new NextMock();

        $this->routeMap->register('GET', $primaryTarget, $primaryHandler);
        $this->routeMap->register('GET', $secondaryTarget, $secondaryHandler);

        $this->request = $this->request->withRequestTarget($requestTarget);

        // Act
        $route = $this->routeMap->getRoute($this->request);

        // Assert
        $this->assertNotNull($route);
        $this->dispatch($route);
        $this->assertTrue($primaryHandler->called);
        $this->assertFalse($secondaryHandler->called);
    }

    public function multipleRouteProvider(): array
    {
        return [
            'Static > Prefix' => [
                '/cats/',
                '/cats/',
                '/cats/*'
            ],
            'Prefix matches' => [
                '/animals/dogs/bear',
                '/animals/dogs/*',
                '/animals/cats/*'
            ],
            // Note: The longest route is also good for 2 points in Settlers of Catan.
            'Longest valid prefix' => [
                '/animals/cats/molly',
                '/animals/cats/*',
                '/animals/*'
            ],
            'Prefix > Pattern' => [
                '/cats/molly',
                '/cats/*',
                '/cats/{id}'
            ],
            'Pattern by order registered' => [
                '/cats/molly',
                '/cats/{id}',
                '/cats/{name}'
            ]
        ];
    }

    public function testReturnsNullWhenNoRouteMatchesRequest(): void
    {
        // Arrange
        $dispatchable = new NextMock();
        $this->routeMap->register('GET', '/cats/aggie', $dispatchable);
        $this->routeMap->register('GET', '/cats/oscar', $dispatchable);
        $this->routeMap->register('GET', '/cats/molly', $dispatchable);
        $this->routeMap->register('GET', '/dogs/bear', $dispatchable);
        $this->request = $this->request->withRequestTarget('/dogs/louisa');

        // Act
        $route = $this->routeMap->getRoute($this->request);

        // Assert
        $this->assertNull($route);
    }

    public function testMatchesVariablesInPath(): void
    {
        // Arrange
        $dispatchable = new NextMock();

        $this->routeMap->register('GET', '/pets/{type}/{name}', $dispatchable);

        $this->request = $this->request->withRequestTarget('/pets/cats/molly');

        // Act
        $route = $this->routeMap->getRoute($this->request);

        // Assert
        $this->assertNotNull($route);
        $this->dispatch($route);
        $this->assertTrue($dispatchable->called);
        $vars = $route->getPathVariables();
        $this->assertEquals('cats', $vars['type']);
        $this->assertEquals('molly', $vars['name']);
    }

    public function testMapsHandlersByMethod(): void
    {
        // Arrange
        $target = '/cats/{name}';
        $getHandler = new NextMock();
        $putHandler = new NextMock();

        $this->routeMap->register('GET', $target, $getHandler);
        $this->routeMap->register('PUT', $target, $putHandler);

        $this->request = $this->request
            ->withRequestTarget('/cats/molly')
            ->withMethod('PUT');

        // Act
        $route = $this->routeMap->getRoute($this->request);

        // Assert
        $this->assertNotNull($route);
        $this->dispatch($route);
        $this->assertFalse($getHandler->called);
        $this->assertTrue($putHandler->called);
    }
}
