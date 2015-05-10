<?php

namespace WellRESTed\Dispatching;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class DispatchStack implements DispatchStackInterface
{
    private $stack;
    private $dispatcher;

    /**
     * @param DispatcherInterface $dispatcher
     */
    public function __construct(DispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
        $this->stack = [];
    }

    /**
     * Push a new middleware onto the stack.
     *
     * This method MUST preserve the order in which middleware added.
     *
     * @param mixed $middleware Middleware to dispatch in sequence
     * @return self
     */
    public function add($middleware)
    {
        $this->stack[] = $middleware;
        return $this;
    }

    /**
     * Dispatch the contained middleware in the order in which they were added.
     *
     * The first middleware added to the stack MUST be the first to be
     * dispatched.
     *
     * Each middleware, when dispatched, MUST receive a $next callable that
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
    public function dispatch(ServerRequestInterface $request, ResponseInterface $response, $next)
    {
        $chain = $this->getCallableChain();
        $response = $chain($request, $response);
        return $next($request, $response);
    }

    // ------------------------------------------------------------------------

    private function getCallableChain()
    {
        $dispatcher = $this->dispatcher;

        // No-op function to use as the final middleware's $mext.
        $next = function ($request, $response) {
            return $response;
        };

        // Create a chain of callables.
        //
        // Each callable wil take $request and $response parameters, and will
        // contain a dispatcher, the associated middleware, and a $next
        // that is the links to the next middleware in the chain.
        foreach (array_reverse($this->stack) as $middleware) {
            $next = function ($request, $response) use ($dispatcher, $middleware, $next) {
                return $dispatcher->dispatch($middleware, $request, $response, $next);
            };
        }

        return $next;
    }
}
