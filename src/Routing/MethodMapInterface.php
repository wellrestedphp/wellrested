<?php

namespace WellRESTed\Routing;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WellRESTed\MiddlewareInterface;

/**
 * Maps HTTP methods to middleware
 */
interface MethodMapInterface extends MiddlewareInterface
{
    /**
     * Evaluate $request's method and dispatches matching middleware.
     *
     * Implementations MUST pass $request, $response, and $next to the matching
     * middleware.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable $next
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next);

    /**
     * Register middleware with a method.
     *
     * $method may be:
     * - A single verb ("GET"),
     * - A comma-separated list of verbs ("GET,PUT,DELETE")
     * - "*" to indicate any method.
     *
     * $middleware may be:
     * - An instance implementing MiddlewareInterface
     * - A string containing the fully qualified class name of a class
     *     implementing MiddlewareInterface
     * - A callable that returns an instance implementing MiddleInterface
     * - A callable matching the signature of MiddlewareInterface::dispatch
     * @see DispatcherInterface::dispatch
     *
     * @param string $method
     * @param mixed $middleware
     */
    public function register($method, $middleware);
}
