<?php

namespace WellRESTed\Routing;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Dispatcher
{
    /**
     * @param $middleware
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return mixed
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
