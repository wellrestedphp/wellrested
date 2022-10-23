<?php

declare(strict_types=1);

namespace WellRESTed\Dispatching;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WellRESTed\Message\Response;
use WellRESTed\Message\ServerRequest;
use WellRESTed\Server;
use WellRESTed\Test\Doubles\HandlerDouble;
use WellRESTed\Test\Doubles\MiddlewareDouble;
use WellRESTed\Test\Doubles\NextDouble;
use WellRESTed\Test\TestCase;

class DispatchQueueTest extends TestCase
{
    private ServerRequestInterface $request;
    private ResponseInterface $response;
    private NextDouble $next;
    private Server $server;
    private DispatchQueue $queue;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new ServerRequest();
        $this->response = new Response();
        $this->next = new NextDouble();
        $this->server = new Server();
        $this->queue = new DispatchQueue($this->server);
    }

    private function dispatch(): ResponseInterface
    {
        return call_user_func(
            $this->queue,
            $this->request,
            $this->response,
            $this->next
        );
    }

    // -------------------------------------------------------------------------

    public function testDispatchesMiddlewareInOrderAdded(): void
    {
        // Arrange

        // Each middleware will add its "name" to this array.
        $callOrder = [];
        $this->queue->add(function ($request, $response, $next) use (&$callOrder) {
            $callOrder[] = 'first';
            return $next($request, $response);
        });
        $this->queue->add(function ($request, $response, $next) use (&$callOrder) {
            $callOrder[] = 'second';
            return $next($request, $response);
        });
        $this->queue->add(function ($request, $response, $next) use (&$callOrder) {
            $callOrder[] = 'third';
            return $next($request, $response);
        });

        // Act
        $this->dispatch();

        // Assert
        $this->assertEquals(['first', 'second', 'third'], $callOrder);
    }

    public function testCallsNextAfterDispatchingEmptyQueue(): void
    {
        // Act
        $this->dispatch();

        // Assert
        $this->assertTrue($this->next->called);
    }

    public function testCallsNextAfterDispatchingQueue(): void
    {
        // Arrange
        $middleware = new MiddlewareDouble();
        $this->queue->add($middleware);
        $this->queue->add($middleware);
        $this->queue->add($middleware);

        // Act
        $this->dispatch();

        // Assert
        $this->assertTrue($this->next->called);
    }

    public function testDoesNotCallNextWhenQueueStopsEarly(): void
    {
        // Arrange
        $middleware = new MiddlewareDouble();
        $handler = new HandlerDouble(new Response(200));
        $this->queue->add($middleware);
        $this->queue->add($handler);

        // Act
        $this->dispatch();

        // Assert
        $this->assertFalse($this->next->called);
    }
}
