<?php

namespace WellRESTed\Dispatching;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WellRESTed\MiddlewareInterface;

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
        } elseif (is_array($middleware)) {
            $middleware = $this->getDispatchStack($middleware);
        }
        if (is_callable($middleware)) {
            return $middleware($request, $response, $next);
        } elseif ($middleware instanceof ResponseInterface) {
            return $middleware;
        } else {
            throw new DispatchException("Unable to dispatch middleware.");
        }
    }

    protected function getDispatchStack($middlewares)
    {
        $stack = new DispatchStack($this);
        foreach ($middlewares as $middleware) {
            $stack->add($middleware);
        }
        return $stack;
    }
}
