<?php

namespace WellRESTed\Dispatching;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Dispatches an ordered sequence of middleware.
 */
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
     * The first middleware that was added is dispatched first.
     *
     * Each middleware, when dispatched, receives a $next callable that, when
     * called, will dispatch the next middleware in the sequence.
     *
     * When the stack is dispatched empty, or when all middleware in the stack
     * call the $next argument they were passed, this method will call the
     * $next it received.
     *
     * When any middleware in the stack returns a response without calling its
     * $next, the stack will not call the $next it received.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable $next
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next)
    {
        $dispatcher = $this->dispatcher;

        // This flag will be set to true when the last middleware calls $next.
        $stackCompleted = false;

        // The final middleware's $next returns $response unchanged and sets
        // the $stackCompleted flag to indicate the stack has completed.
        $chain = function ($request, $response) use (&$stackCompleted) {
            $stackCompleted = true;
            return $response;
        };

        // Create a chain of callables.
        //
        // Each callable wil take $request and $response parameters, and will
        // contain a dispatcher, the associated middleware, and a $next
        // that is the links to the next middleware in the chain.
        foreach (array_reverse($this->stack) as $middleware) {
            $chain = function ($request, $response) use ($dispatcher, $middleware, $chain) {
                return $dispatcher->dispatch($middleware, $request, $response, $chain);
            };
        }

        $response = $chain($request, $response);

        if ($stackCompleted) {
            return $next($request, $response);
        } else {
            return $response;
        }
    }
}
