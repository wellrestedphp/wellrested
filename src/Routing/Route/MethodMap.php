<?php

namespace WellRESTed\Routing\Route;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WellRESTed\Dispatching\DispatcherInterface;
use WellRESTed\MiddlewareInterface;

/**
 * @internal
 */
class MethodMap implements MiddlewareInterface
{
    /** @var DispatcherInterface */
    private $dispatcher;
    /** @var array */
    private $map;

    // -------------------------------------------------------------------------

    public function __construct(DispatcherInterface $dispatcher)
    {
        $this->map = [];
        $this->dispatcher = $dispatcher;
    }

    /**
     * Register a dispatchable (e.g.m handler or middleware) with a method.
     *
     * $method may be:
     * - A single verb ("GET"),
     * - A comma-separated list of verbs ("GET,PUT,DELETE")
     * - "*" to indicate any method.
     *
     * $dispatchable may be anything a Dispatcher can dispatch.
     * @see DispatcherInterface::dispatch
     *
     * $dispatchable may also be null, in which case any previously set
     * handlers and middle for that method or methods will be unset.
     *
     * @param string $method
     * @param mixed $dispatchable
     */
    public function register(string $method, $dispatchable): void
    {
        $methods = explode(',', $method);
        $methods = array_map('trim', $methods);
        foreach ($methods as $method) {
            $this->map[$method] = $dispatchable;
        }
    }

    // -------------------------------------------------------------------------
    // MiddlewareInterface

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable $next
     * @return ResponseInterface
     */
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        $next
    ) {
        $method = $request->getMethod();
        // Dispatch middleware registered with the explicitly matching method.
        if (isset($this->map[$method])) {
            $middleware = $this->map[$method];
            return $this->dispatchMiddleware($middleware, $request, $response, $next);
        }
        // For HEAD, dispatch GET by default.
        if ($method === 'HEAD' && isset($this->map['GET'])) {
            $middleware = $this->map['GET'];
            return $this->dispatchMiddleware($middleware, $request, $response, $next);
        }
        // Dispatch * middleware, if registered.
        if (isset($this->map['*'])) {
            $middleware = $this->map['*'];
            return $this->dispatchMiddleware($middleware, $request, $response, $next);
        }
        // Respond describing the allowed methods, either as a 405 response or
        // in response to an OPTIONS request.
        if ($method === 'OPTIONS') {
            $response = $response->withStatus(200);
        } else {
            $response = $response->withStatus(405);
        }
        return $this->addAllowHeader($response);
    }

    // -------------------------------------------------------------------------

    private function addAllowHeader(ResponseInterface $response): ResponseInterface
    {
        $methods = join(',', $this->getAllowedMethods());
        return $response->withHeader('Allow', $methods);
    }

    /**
     * @return string[]
     */
    private function getAllowedMethods(): array
    {
        $methods = array_keys($this->map);
        // Add HEAD if GET is allowed and HEAD is not present.
        if (in_array('GET', $methods) && !in_array('HEAD', $methods)) {
            $methods[] = 'HEAD';
        }
        // Add OPTIONS if not already present.
        if (!in_array('OPTIONS', $methods)) {
            $methods[] = 'OPTIONS';
        }
        return $methods;
    }

    /**
     * @param mixed $middleware
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable $next
     * @return ResponseInterface
     */
    private function dispatchMiddleware(
        $middleware,
        ServerRequestInterface $request,
        ResponseInterface $response,
        $next
    ) {
        return $this->dispatcher->dispatch($middleware, $request, $response, $next);
    }
}
