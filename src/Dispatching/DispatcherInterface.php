<?php

namespace WellRESTed\Dispatching;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Dispatches handlers and middleware
 */
interface DispatcherInterface
{
    /**
     * Dispatch a handler or middleware and return the response.
     *
     * Dispatchables (middleware and handlers) comes in a number of varieties
     * (e.g., instance, string, callable). DispatcherInterface interface unpacks
     * the dispatchable and dispatches it.
     *
     * Implementations MUST be able to dispatch the following:
     *   - An instance implementing one of these interfaces:
     *     - Psr\Http\Server\RequestHandlerInterface
     *     - Psr\Http\Server\MiddlewareInterface
     *     - WellRESTed\MiddlewareInterface
     *     - Psr\Http\Message\ResponseInterface
     *   - A string containing the fully qualified class name of a class
     *        implementing one of the interfaces listed above.
     *   - A callable that returns an instance implementing one of the
     *       interfaces listed above.
     *   - A callable with a signature matching the signature of
     *       WellRESTed\MiddlewareInterface::__invoke
     *   - An array containing any of the items in this list.
     *
     * Implementation MAY dispatch other types of middleware.
     *
     * When an implementation receives a $dispatchable that is not of a type it
     * can dispatch, it MUST throw a DispatchException.
     *
     * @param mixed $dispatchable
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable $next
     * @return ResponseInterface
     * @throws DispatchException Unable to dispatch $middleware
     */
    public function dispatch(
        $dispatchable,
        ServerRequestInterface $request,
        ResponseInterface $response,
        $next
    );
}
