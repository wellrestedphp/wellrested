<?php

namespace WellRESTed\Dispatching;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface DispatcherInterface
{
    /**
     * Dispatch middleware and return the response.
     *
     * This method MUST pass $request, $response, and $next to the middleware
     * to be dispatched.
     *
     * $middleware comes in a number of varieties (e.g., instance, string,
     * callable). DispatcherInterface interface exist to unpack the middleware
     * and dispatch it.
     *
     * Implementations MUST be able to dispatch the following:
     * - An instance implementing MiddlewareInterface
     * - A string containing the fully qualified class name of a class
     *        implementing MiddlewareInterface
     * - A callable that returns an instance implementing MiddlewareInterface
     * - A callable with a signature matching MiddlewareInterface::dispatch
     *
     * Implementation MAY dispatch other types of middleware.
     *
     * When an implementation recieves a $middware that is not of a type it can
     * dispatch, it MUST throw a DispatchException.
     *
     * @param mixed $middleware
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable $next
     * @return ResponseInterface
     * @throws DispatchException Unable to dispatch $middleware
     */
    public function dispatch($middleware, ServerRequestInterface $request, ResponseInterface $response, $next);
}
