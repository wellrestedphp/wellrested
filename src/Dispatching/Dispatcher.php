<?php

namespace WellRESTed\Dispatching;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Dispatches handlers and middleware
 */
class Dispatcher implements DispatcherInterface
{
    /**
     * Dispatch a handler or middleware and return the response.
     *
     * Dispatcher can dispatch any of the following:
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
     * When Dispatcher receives a $dispatchable that is not of a type it
     * can dispatch, it throws a DispatchException.
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
    ) {
        if (is_callable($dispatchable)) {
            $dispatchable = $dispatchable($request, $response, $next);
        } elseif (is_string($dispatchable)) {
            $dispatchable = new $dispatchable();
        } elseif (is_array($dispatchable)) {
            $dispatchable = $this->getDispatchStack($dispatchable);
        }

        if (is_callable($dispatchable)) {
            return $dispatchable($request, $response, $next);
        } elseif ($dispatchable instanceof RequestHandlerInterface) {
            return $dispatchable->handle($request);
        } elseif ($dispatchable instanceof MiddlewareInterface) {
            $delegate = new DispatcherDelegate($response, $next);
            return $dispatchable->process($request, $delegate);
        } elseif ($dispatchable instanceof ResponseInterface) {
            return $dispatchable;
        } else {
            throw new DispatchException('Unable to dispatch middleware.');
        }
    }

    protected function getDispatchStack($dispatchables)
    {
        $stack = new DispatchStack($this);
        foreach ($dispatchables as $dispatchable) {
            $stack->add($dispatchable);
        }
        return $stack;
    }
}
