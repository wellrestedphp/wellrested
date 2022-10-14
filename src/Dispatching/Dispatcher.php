<?php

namespace WellRESTed\Dispatching;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Runs a handler or middleware with a request and returns the response.
 */
class Dispatcher implements DispatcherInterface
{
    private ?ContainerInterface $container;

    public function __construct(?ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * Run a handler or middleware with a request and return the response.
     *
     * Dispatcher can dispatch any of the following:
     *   - An instance implementing one of these interfaces:
     *     - Psr\Http\Server\RequestHandlerInterface
     *     - Psr\Http\Server\MiddlewareInterface
     *     - WellRESTed\MiddlewareInterface
     *     - Psr\Http\Message\ResponseInterface
     *   - A string matching the name of a service in the depdency container
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
        if (is_string($dispatchable)) {
            // String: resolve from DI or instantiate from class name.
            if ($this->container && $this->container->has($dispatchable)) {
                $dispatchable = $this->container->get($dispatchable);
            } else {
                $dispatchable = new $dispatchable();
            }
        } elseif (is_callable($dispatchable)) {
            // Callable: may be a factory function or double pass middleware.
            $dispatchable = $dispatchable($request, $response, $next);
        } elseif (is_array($dispatchable)) {
            // Array: convert to DispatchStack.
            $dispatchable = $this->getDispatchStack($dispatchable);
        }

        if (is_callable($dispatchable)) {
            // Double pass
            return $dispatchable($request, $response, $next);
        } elseif ($dispatchable instanceof RequestHandlerInterface) {
            // PSR-15 Handler
            return $dispatchable->handle($request);
        } elseif ($dispatchable instanceof MiddlewareInterface) {
            // PSR-15 Middleware
            $adapter = new Psr15Adapter($response, $next);
            return $dispatchable->process($request, $adapter);
        } elseif ($dispatchable instanceof ResponseInterface) {
            // PSR-7 Response
            return $dispatchable;
        } else {
            throw new DispatchException('Unable to dispatch handler.');
        }
    }

    /**
     * @param mixed[] $dispatchables
     * @return DispatchStack
     */
    private function getDispatchStack($dispatchables)
    {
        $stack = new DispatchStack($this);
        foreach ($dispatchables as $dispatchable) {
            $stack->add($dispatchable);
        }
        return $stack;
    }
}
