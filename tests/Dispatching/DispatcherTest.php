<?php

namespace WellRESTed\Dispatching;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use WellRESTed\Message\Response;
use WellRESTed\Message\ServerRequest;
use WellRESTed\MiddlewareInterface;
use WellRESTed\Test\Doubles\ContainerDouble;
use WellRESTed\Test\Doubles\HandlerDouble;
use WellRESTed\Test\Doubles\NextMock;
use WellRESTed\Test\TestCase;

class DispatcherTest extends TestCase
{
    private ServerRequestInterface $request;
    private ResponseInterface $response;
    private NextMock $next;
    private ResponseInterface $stubResponse;

    protected function setUp(): void
    {
        $this->request = new ServerRequest();
        $this->response = new Response(500);
        $this->next = new NextMock();
        $this->stubResponse = new Response();
    }

    /**
     * Dispatch the provided dispatchable using the class under test and the
     * ivars $request, $response, and $next. Return the response.
     * @param $dispatchable
     * @return ResponseInterface
     */
    private function dispatch(
        $dispatchable,
        ?ContainerInterface $container = null
    ): ResponseInterface {
        $dispatcher = new Dispatcher($container);
        return $dispatcher->dispatch(
            $dispatchable,
            $this->request,
            $this->response,
            $this->next
        );
    }

    // -------------------------------------------------------------------------
    // PSR-15 Handler

    public function testDispatchesPsr15Handler(): void
    {
        // Arrange
        $handler = new HandlerDouble($this->stubResponse);
        // Act
        $response = $this->dispatch($handler);
        // Assert
        $this->assertSame($this->stubResponse, $response);
    }

    public function testDispatchesPsr15HandlerFromFactory(): void
    {
        // Arrange
        $factory = function () {
            return new HandlerDouble($this->stubResponse);
        };
        // Act
        $response = $this->dispatch($factory);
        // Assert
        $this->assertSame($this->stubResponse, $response);
    }

    public function testDispatchesPsr15HandlerFromContainer(): void
    {
        // Arrange
        $handler = new HandlerDouble($this->stubResponse);
        $container = new ContainerDouble(['service' => $handler]);
        // Act
        $response = $this->dispatch('service', $container);
        // Assert
        $this->assertSame($this->stubResponse, $response);
    }

    // -------------------------------------------------------------------------
    // PSR-15 Middleware

    public function testDispatchesPsr15Middleware(): void
    {
        // Arrange
        $this->next->upstreamResponse = $this->stubResponse;
        $middleware = new MiddlewareDouble();
        // Act
        $response = $this->dispatch($middleware);
        // Assert
        $this->assertSame($this->stubResponse, $response);
    }

    public function testDispatchesPsr15MiddlewareFromFactory(): void
    {
        // Arrange
        $this->next->upstreamResponse = $this->stubResponse;
        $factory = function () {
            return new MiddlewareDouble();
        };
        // Act
        $response = $this->dispatch($factory);
        // Assert
        $this->assertSame($this->stubResponse, $response);
    }

    public function testDispatchesPsr15MiddlewareFromContainer(): void
    {
        // Arrange
        $this->next->upstreamResponse = $this->stubResponse;
        $middleware = new MiddlewareDouble();
        $container = new ContainerDouble(['service' => $middleware]);
        // Act
        $response = $this->dispatch('service', $container);
        // Assert
        $this->assertSame($this->stubResponse, $response);
    }

    // -------------------------------------------------------------------------
    // Double-Pass Middleware Callable

    public function testDispatchesDoublePassMiddlewareCallable(): void
    {
        // Arrange
        $doublePass = function ($request, $response, $next) {
            return $next($request, $this->stubResponse);
        };
        // Act
        $response = $this->dispatch($doublePass);
        // Assert
        $this->assertSame($this->stubResponse, $response);
    }

    public function testDispatchesDoublePassMiddlewareCallableFromFactory(): void
    {
        // Arrange
        $factory = function () {
            return function ($request, $response, $next) {
                return $next($request, $this->stubResponse);
            };
        };
        // Act
        $response = $this->dispatch($factory);
        // Assert
        $this->assertSame($this->stubResponse, $response);
    }

    public function testDispatchesDoublePassMiddlewareCallableFromContainer(): void
    {
        // Arrange
        $doublePass = function ($request, $response, $next) {
            return $next($request, $this->stubResponse);
        };
        $container = new ContainerDouble(['service' => $doublePass]);
        // Act
        $response = $this->dispatch('service', $container);
        // Assert
        $this->assertSame($this->stubResponse, $response);
    }

    // -------------------------------------------------------------------------
    // Double-Pass Middleware Instance

    public function testDispatchesDoublePassMiddlewareInstance(): void
    {
        // Arrange
        $doublePass = new DoublePassMiddlewareDouble();
        // Act
        $response = $this->dispatch($doublePass);
        // Assert
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testDispatchesDoublePassMiddlewareInstanceFromFactory(): void
    {
        // Arrange
        $factory = function () {
            return new DoublePassMiddlewareDouble();
        };
        // Act
        $response = $this->dispatch($factory);
        // Assert
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testDispatchesDoublePassMiddlewareInstanceFromContainer(): void
    {
        // Arrange
        $doublePass = new DoublePassMiddlewareDouble();
        $container = new ContainerDouble(['service' => $doublePass]);
        // Act
        $response = $this->dispatch('service', $container);
        // Assert
        $this->assertEquals(200, $response->getStatusCode());
    }

    // -------------------------------------------------------------------------
    // String

    public function testDispatchesStringByInsantiatingFromStringValue(): void
    {
        // Act
        $response = $this->dispatch(DoublePassMiddlewareDouble::class);
        // Assert
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testDispatchesStringFromContainerInsteadOfValueWhenAble(): void
    {
        // Arrange
        $handler = new HandlerDouble($this->stubResponse);
        $serviceName = DoublePassMiddlewareDouble::class;
        $container = new ContainerDouble([$serviceName => $handler]);
        // Act
        $response = $this->dispatch($serviceName, $container);
        // Assert
        $this->assertSame($this->stubResponse, $response);
    }

    // -------------------------------------------------------------------------
    // Arrays

    public function testDispatchesArrayAsDispatchStack(): void
    {
        // Arrange
        $doublePass = new DoublePassMiddlewareDouble();
        // Act
        $response = $this->dispatch([$doublePass]);
        // Assert
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testDispatchesArrayWithServicesFromContainer(): void
    {
        // Arrange
        $middleware = new MiddlewareDouble();
        $handler = new HandlerDouble($this->stubResponse);
        $container = new ContainerDouble([
            'middleware' => $middleware,
            'handler' => $handler
        ]);
        // Act
        $response = $this->dispatch(['middleware', 'handler'], $container);
        // Assert
        $this->assertSame($this->stubResponse, $response);
    }

    public function testThrowsExceptionWhenUnableToDispatch(): void
    {
        $this->expectException(DispatchException::class);
        $this->dispatch(null);
    }
}

// -----------------------------------------------------------------------------
// Doubles

/**
 * Double pass middleware that sends a response with a 200 status to $next
 * and return the response.
 *
 * This class has no constructor so that we can test instantiating from string.
 */
class DoublePassMiddlewareDouble implements MiddlewareInterface
{
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        $next
    ) {
        $response = $response->withStatus(200);
        return $next($request, $response);
    }
}

// -----------------------------------------------------------------------------

/**
 * PSR-15 Middleware that passes the request to the delegate and returns the
 * delegate's response
 */
class MiddlewareDouble implements \Psr\Http\Server\MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        return $handler->handle($request);
    }
}
