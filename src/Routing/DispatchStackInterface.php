<?php

namespace WellRESTed\Routing;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface DispatchStackInterface extends MiddlewareInterface
{
    /**
     * Push a new middleware onto the stack.
     *
     * @param mixed $middleware Middleware to dispatch in sequence
     * @return self
     */
    public function add($middleware);

    /**
     * Dispatch the contained middleware in the order in which they were added.
     *
     * The first middleware added to the stack is the first to be dispatched.
     *
     * Each middleware, when dispatched, will receive a $next callable that
     * dispatches the middleware that follows it. The only exception to this is
     * the last middleware in the stack which much receive a $next callable the
     * returns the response unchanged.
     *
     * If the instance is dispatched with no middleware added, the instance
     * MUST call $next passing $request and $response and return the returned
     * response.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable $next
     * @return ResponseInterface
     */
    public function dispatch(ServerRequestInterface $request, ResponseInterface $response, $next);
}
