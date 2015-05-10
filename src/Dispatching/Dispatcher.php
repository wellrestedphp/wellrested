<?php

namespace WellRESTed\Dispatching;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WellRESTed\Routing\MiddlewareInterface;

class Dispatcher implements DispatcherInterface
{
    /**
     * @param mixed $middleware
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable $next
     * @return ResponseInterface
     * @throws DispatchException Unable to dispatch $middleware
     */
    public function dispatch($middleware, ServerRequestInterface $request, ResponseInterface $response, $next)
    {
        if (is_callable($middleware)) {
            $middleware = $middleware($request, $response, $next);
        } elseif (is_string($middleware)) {
            $middleware = new $middleware();
        }
        if ($middleware instanceof MiddlewareInterface) {
            return $middleware->dispatch($request, $response, $next);
        } elseif ($middleware instanceof ResponseInterface) {
            return $middleware;
        } else {
            throw new DispatchException("Unable to dispatch middleware.");
        }
    }
}
