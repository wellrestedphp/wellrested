<?php

declare(strict_types=1);

namespace WellRESTed;

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use WellRESTed\Dispatching\DispatcherInterface;
use WellRESTed\Message\Response;
use WellRESTed\Message\ServerRequest;
use WellRESTed\Test\Doubles\ContainerDouble;
use WellRESTed\Test\Doubles\HandlerDouble;
use WellRESTed\Test\Doubles\MiddlewareMock;
use WellRESTed\Test\Doubles\NextMock;
use WellRESTed\Test\Doubles\TransmitterDouble;
use WellRESTed\Test\TestCase;

class ServerTest extends TestCase
{
    use ProphecyTrait;

    private TransmitterDouble $transmitter;
    private Server $server;

    protected function setUp(): void
    {
        parent::setUp();

        $this->transmitter = new TransmitterDouble();

        $this->server = new Server();
        $this->server->setTransmitter($this->transmitter);
    }

    // -------------------------------------------------------------------------

    public function testDispatchesMiddlewareStack(): void
    {
        // Arrange
        // Add three middleware. Each will add a string to this array.
        $steps = [];
        $this->server->add(
            function ($rqst, $resp, $next) use (&$steps) {
                $steps[] = 'first';
                return $next($rqst, $resp);
            }
        );
        $this->server->add(
            function ($rqst, $resp, $next) use (&$steps) {
                $steps[] = 'second';
                return $next($rqst, $resp);
            }
        );
        $this->server->add(
            function ($rqst, $resp, $next) use (&$steps) {
                $steps[] = 'third';
                return $next($rqst, $resp);
            }
        );

        // Act
        $this->server->respond();

        // Assert
        $this->assertEquals(['first', 'second', 'third'], $steps);
    }

    public function testDispatchedRequest(): void
    {
        // Arrange
        $request = new ServerRequest();
        $middleware = new MiddlewareMock();
        $this->server->setRequest($request);
        $this->server->add($middleware);

        // Act
        $this->server->respond();

        // Assert
        $this->assertSame($request, $middleware->request);
    }

    public function testDispatchesResponse(): void
    {
        // Arrange
        $response = new Response();
        $middleware = new MiddlewareMock();
        $this->server->setResponse($response);
        $this->server->add($middleware);

        // Act
        $this->server->respond();

        // Assert
        $this->assertSame($response, $middleware->response);
    }

    // -------------------------------------------------------------------------
    // Respond

    public function testRespondSendsResponseToTransmitter(): void
    {
        // Arrange
        $expectedResponse = new Response(200);
        $handler = new HandlerDouble($expectedResponse);
        $this->server->add(new MiddlewareMock());
        $this->server->add(new MiddlewareMock());
        $this->server->add($handler);

        // Act
        $this->server->respond();

        // Assert
        $this->assertEquals($expectedResponse, $this->transmitter->response);
    }

    // -------------------------------------------------------------------------
    // Router

    public function testCreatesRouterWithDispatcher(): void
    {
        // Arrange

        // Configure the server with a double for the dispatcher.
        $dispatcher = $this->prophesize(DispatcherInterface::class);
        $dispatcher->dispatch(Argument::cetera())
            ->willReturn(new Response(200));
        $this->server->setDispatcher($dispatcher->reveal());

        // Create a new router that should get the custom dispatcher.
        $router = $this->server->createRouter();
        $router->register('GET', '/', 'middleware');

        // Act
        $request = (new ServerRequest())
            ->withMethod('GET')
            ->withRequestTarget('/');
        $router($request, new Response(), new NextMock());

        // Assert
        $dispatcher->dispatch(Argument::cetera())
            ->shouldHaveBeenCalled();
    }

    // -------------------------------------------------------------------------
    // Dependency Injection

    public function testRoutesResolveServicesFromContainer(): void
    {
        // Arrange
        $response = new Response(200);
        $handler = new HandlerDouble($response);
        $container = new ContainerDouble(['handler' => $handler]);
        $this->server->setContainer($container);
        $router = $this->server->createRouter();
        $router->register('GET', '/', 'handler');
        $this->server->add($router);

        // Act
        $request = (new ServerRequest())
            ->withMethod('GET')
            ->withRequestTarget('/');
        $this->server->setRequest($request);
        $this->server->respond();

        // Assert
        $this->assertEquals($response, $this->transmitter->response);
    }

    // -------------------------------------------------------------------------
    // Attributes

    public function testAddsAttributesToRequest(): void
    {
        // Arrange
        $this->server->setAttributes([
            'name' => 'value'
        ]);
        $middleware = new MiddlewareMock();
        $this->server->add($middleware);

        // Act
        $this->server->respond();

        // Assert
        $this->assertEquals('value', $middleware->request?->getAttribute('name'));
    }

    // -------------------------------------------------------------------------
    // End of Stack

    public function testReturnsLastDoublePassResponseAtEndOfStack(): void
    {
        // Arrange
        $defaultResponse = new Response(404);
        $this->server->setResponse($defaultResponse);
        $this->server->add(new MiddlewareMock());

        // Act
        $this->server->respond();

        // Assert
        $this->assertEquals($defaultResponse, $this->transmitter->response);
    }
}
