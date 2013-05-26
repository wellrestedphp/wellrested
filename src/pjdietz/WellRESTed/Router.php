<?php

/**
 * pjdietz\WellRESTed\Router
 *
 * @author PJ Dietz <pj@pjdietz.com>
 * @copyright Copyright 2013 by PJ Dietz
 * @license MIT
 */

namespace pjdietz\WellRESTed;

use pjdietz\WellRESTed\Interfaces\HandlerInterface;
use pjdietz\WellRESTed\Interfaces\RequestInterface;
use pjdietz\WellRESTed\Interfaces\ResponseInterface;
use pjdietz\WellRESTed\Interfaces\RouteInterface;
use pjdietz\WellRESTed\Interfaces\RouterInterface;

/**
 * Router
 *
 * A Router uses a table of Routes to find the appropriate Handler for a request.
 */
class Router implements RouterInterface
{
    /** @var string  Fully qualified name for the interface for handlers */
    const HANDLER_INTERFACE = '\\pjdietz\\WellRESTed\\Interfaces\\HandlerInterface';
    /** @var array  Array of Route objects */
    private $routes;

    /** Create a new Router. */
    public function __construct()
    {
        $this->routes = array();
    }

    /**
     * Append a new Route instance to the Router's route table.
     *
     * @param RouteInterface $route
     */
    public function addRoute(RouteInterface $route)
    {
        $this->routes[] = $route;
    }

    /**
     * Return the response built by the handler based on the request
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     */
    public function getResponse(RequestInterface $request = null)
    {
        if (is_null($request)) {
            $request = Request::getRequest();
        }

        $path = $request->getPath();

        foreach ($this->routes as $route) {
            /** @var RouteInterface $route */
            if (preg_match($route->getPattern(), $path, $matches)) {
                $handlerClassName = $route->getHandler();
                if (is_subclass_of($handlerClassName, self::HANDLER_INTERFACE)) {
                    /** @var HandlerInterface $handler */
                    $handler = new $handlerClassName();
                    $handler->setRequest($request);
                    $handler->setArguments($matches);
                    $handler->setRouter($this);
                    return $handler->getResponse();
                } else {
                    return $this->getNoRouteResponse($request);
                }
            }
        }

        return $this->getNoRouteResponse($request);
    }

    /**
     * Prepare a resonse indicating a 404 Not Found error
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     */
    protected function getNoRouteResponse(RequestInterface $request)
    {
        $response = new Response(404);
        $response->body = 'No resource at ' . $request->getPath();
        return $response;
    }

}
