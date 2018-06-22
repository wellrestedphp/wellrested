<?php

namespace WellRESTed\Routing;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WellRESTed\MiddlewareInterface;

/**
 * Maps HTTP methods to handlers and middleware
 */
interface MethodMapInterface extends MiddlewareInterface
{
    /**
     * Evaluate $request's method and dispatches matching dispatchable.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable $next
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next);

    /**
     * Register a dispatchable (handler or middleware) with a method.
     *
     * $method may be:
     * - A single verb ("GET"),
     * - A comma-separated list of verbs ("GET,PUT,DELETE")
     * - "*" to indicate any method.
     *
     * $dispatchable may be anything a Dispatcher can dispatch.
     * @see DispatcherInterface::dispatch
     *
     * @param string $method
     * @param mixed $dispatchable
     */
    public function register($method, $dispatchable);
}
