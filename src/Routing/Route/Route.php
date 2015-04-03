<?php

namespace WellRESTed\Routing\Route;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WellRESTed\Routing\Dispatcher;

abstract class Route implements RouteInterface
{
    private $middleware;

    public function __construct($middleware)
    {
        $this->middleware = $middleware;
    }

    public function dispatch(ServerRequestInterface $request, ResponseInterface &$response)
    {
        $dispatcher = new Dispatcher();
        $dispatcher->dispatch($this->middleware, $request, $response);
    }
}
