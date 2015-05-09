<?php

namespace WellRESTed\Routing;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Dispatcher implements DispatcherInterface
{
    /**
     * @param $middleware
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws \InvalidArgumentException $middleware is not a valid type.
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
            throw new \InvalidArgumentException("Unable to dispatch middleware.");
        }
    }
}
