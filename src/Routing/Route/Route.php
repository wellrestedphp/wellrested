<?php

namespace WellRESTed\Routing\Route;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WellRESTed\Routing\MethodMapInterface;

abstract class Route implements RouteInterface
{
    /** @var string */
    protected $target;
    /** @var MethodMapInterface  */
    protected $methodMap;

    public function __construct($target, $methodMap)
    {
        $this->target = $target;
        $this->methodMap = $methodMap;
    }

    /**
     * Return the instance mapping methods to middleware for this route.
     *
     * @return MethodMapInterface
     */
    public function getMethodMap()
    {
        return $this->methodMap;
    }

    /**
     * @return string
     */
    public function getTarget()
    {
        return $this->target;
    }

    public function dispatch(ServerRequestInterface $request, ResponseInterface $response, $next)
    {
        return $this->methodMap->dispatch($request, $response, $next);
    }
}
