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
     */
    public function dispatch($middleware, ServerRequestInterface $request, ResponseInterface &$response)
    {
        if (is_callable($middleware)) {
            $middleware = $middleware($request, $response);
        } elseif (is_string($middleware)) {
            $middleware = new $middleware();
        }
        if ($middleware instanceof MiddlewareInterface) {
            $middleware->dispatch($request, $response);
        }
    }
}
