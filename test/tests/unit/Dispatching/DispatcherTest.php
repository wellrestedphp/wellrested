<?php

namespace WellRESTed\Test\Unit\Dispatching;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use WellRESTed\Dispatching\Dispatcher;
use WellRESTed\Dispatching\DispatchException;
use WellRESTed\Message\Response;
use WellRESTed\Message\ServerRequest;
use WellRESTed\MiddlewareInterface;
use WellRESTed\Test\Doubles\NextMock;
use WellRESTed\Test\TestCase;

class DispatcherTest extends TestCase
{
    /** @var ServerRequestInterface */
    private $request;
    /** @var ResponseInterface */
    private $response;
    /** @var NextMock */
    private $next;
    /** @var ResponseInterface */
    private $stubResponse;

    public function setUp(): void
    {
        $this->request = new ServerRequest();
        $this->response = new Response();
        $this->next = new NextMock();
        $this->stubResponse = new Response();
    }

    /**
     * Dispatch the provided dispatchable using the class under test and the
     * ivars $request, $response, and $next. Return the response.
     */
    private function dispatch($dispatchable): ResponseInterface
    {
        $dispatcher = new Dispatcher();
        return $dispatcher->dispatch(
            $dispatchable,
            $this->request,
            $this->response,
            $this->next
        );
    }

    // -------------------------------------------------------------------------
    // PSR-15 Handler

    public function testDispatchesPsr15Handler()
    {
        $handler = new HandlerDouble($this->stubResponse);
        $response = $this->dispatch($handler);
        $this->assertSame($this->stubResponse, $response);
    }

    public function testDispatchesPsr15HandlerFromFactory()
    {
        $factory = function () {
            return new HandlerDouble($this->stubResponse);
        };

        $response = $this->dispatch($factory);
        $this->assertSame($this->stubResponse, $response);
    }

    // -------------------------------------------------------------------------
    // PSR-15 Middleware

    public function testDispatchesPsr15MiddlewareWithDelegate() {
        $this->next->upstreamResponse = $this->stubResponse;
        $middleware = new MiddlewareDouble();

        $response = $this->dispatch($middleware);
        $this->assertSame($this->stubResponse, $response);
    }

    public function testDispatchesPsr15MiddlewareFromFactoryWithDelegate() {
        $this->next->upstreamResponse = $this->stubResponse;
        $factory = function () {
            return new MiddlewareDouble();
        };

        $response = $this->dispatch($factory);
        $this->assertSame($this->stubResponse, $response);
    }

    // -------------------------------------------------------------------------
    // Double-Pass Middleware Callable

    public function testDispatchesDoublePassMiddlewareCallable()
    {
        $doublePass = function ($request, $response, $next) {
            return $next($request, $this->stubResponse);
        };

        $response = $this->dispatch($doublePass);
        $this->assertSame($this->stubResponse, $response);
    }

    public function testDispatchesDoublePassMiddlewareCallableFromFactory()
    {
        $factory = function () {
            return function ($request, $response, $next) {
                return $next($request, $this->stubResponse);
            };
        };

        $response = $this->dispatch($factory);
        $this->assertSame($this->stubResponse, $response);
    }

    // -------------------------------------------------------------------------
    // Double-Pass Middleware Instance

    public function testDispatchesDoublePassMiddlewareInstance()
    {
        $doublePass = new DoublePassMiddlewareDouble();
        $response = $this->dispatch($doublePass);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testDispatchesDoublePassMiddlewareInstanceFromFactory()
    {
        $factory = function () {
            return new DoublePassMiddlewareDouble();
        };
        $response = $this->dispatch($factory);
        $this->assertEquals(200, $response->getStatusCode());
    }

    // -------------------------------------------------------------------------
    // String

    public function testDispatchesInstanceFromStringName()
    {
        $response = $this->dispatch(DoublePassMiddlewareDouble::class);
        $this->assertEquals(200, $response->getStatusCode());
    }

    // -------------------------------------------------------------------------
    // Arrays

    public function testDispatchesArrayAsDispatchStack()
    {
        $doublePass = new DoublePassMiddlewareDouble();
        $response = $this->dispatch([$doublePass]);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testThrowsExceptionWhenUnableToDispatch()
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
 * PSR-15 Handler that returns a ResponseInterface stub
 */
class HandlerDouble implements RequestHandlerInterface
{
    /** @var ResponseInterface */
    private $response;
    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->response;
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
