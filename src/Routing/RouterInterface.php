<?php

namespace WellRESTed\Routing;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WellRESTed\MiddlewareInterface;

/**
 * Maps HTTP methods and paths to middleware
 */
interface RouterInterface extends MiddlewareInterface
{
    /**
     * Evaluate $request's path and method and dispatches matching middleware.
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
     * Register middleware with the router for a given path and method.
     *
     * $method may be:
     * - A single verb ("GET")
     * - A comma-separated list of verbs ("GET,PUT,DELETE")
     * - "*" to indicate any method
     * @see MethodMapInterface::register
     *
     * $target may be:
     * - An exact path (e.g., "/path/")
     * - A prefix path ending with "*"" ("/path/*"")
     * - A URI template with one or more variables ("/path/{id}")
     * - A regular expression ("~/cat/([0-9]+)~")
     *
     * $middleware may be:
     * - An instance implementing MiddlewareInterface
     * - A string containing the fully qualified class name of a class
     *     implementing MiddlewareInterface
     * - A callable that returns an instance implementing MiddleInterface
     * - A callable matching the signature of MiddlewareInterface::dispatch
     * @see DispatchedInterface::dispatch
     *
     * @param string $target Request target or pattern to match
     * @param string $method HTTP method(s) to match
     * @param mixed $middleware Middleware to dispatch
     * @return static
     */
    public function register($method, $target, $middleware);

    /**
     * Push a new middleware onto the stack. Middleware for a router runs only
     * when the router has a route matching the request.
     *
     * $middleware may be:
     * - An instance implementing MiddlewareInterface
     * - A string containing the fully qualified class name of a class
     *     implementing MiddlewareInterface
     * - A callable that returns an instance implementing MiddleInterface
     * - A callable matching the signature of MiddlewareInterface::dispatch
     * @see DispatchedInterface::dispatch
     *
     * @param mixed $middleware Middleware to dispatch in sequence
     * @return static
     */
    public function addMiddleware($middleware);
}
