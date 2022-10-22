<?php

declare(strict_types=1);

namespace WellRESTed\Routing;

use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ProphecyInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WellRESTed\Configuration;
use WellRESTed\Dispatching\Dispatcher;
use WellRESTed\Message\Response;
use WellRESTed\Message\ServerRequest;
use WellRESTed\Test\Doubles\MiddlewareMock;
use WellRESTed\Test\Doubles\NextMock;
use WellRESTed\Test\TestCase;

class RouterTest extends TestCase
{
    use ProphecyTrait;

    private Configuration $configuration;
    private ProphecyInterface $routeMap;
    private ServerRequestInterface $request;
    private ResponseInterface $response;
    private Router $router;
    private NextMock $next;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configuration = new Configuration();
        $this->router = new Router(
            new Dispatcher($this->configuration),
            $this->configuration);
        $this->request = new ServerRequest();
        $this->response = new Response();
        $this->next = new NextMock();
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
        $handler = new MiddlewareMock();
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
        $handler = new MiddlewareMock();
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
        $handler = new MiddlewareMock();
        $this->router->register('GET', '/pets/{type}/{name}', $handler);
        $this->configuration->setPathVariablesAttributeName('pathVars');

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
        $handler = new MiddlewareMock();
        $middleware1 = new MiddlewareMock();
        $middleware2 = new MiddlewareMock();
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
        $middleware = new MiddlewareMock();
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
}
