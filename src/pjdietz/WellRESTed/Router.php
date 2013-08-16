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
use pjdietz\WellRESTed\Interfaces\RouteTargetInterface;

/**
 * Router
 *
 * A Router uses a table of Routes to find the appropriate Handler for a request.
 */
class Router extends RouteTarget implements RouterInterface
{
    /** @var string  Fully qualified name for the interface for handlers */
    const ROUTE_TARGET_INTERFACE = '\\pjdietz\\WellRESTed\\Interfaces\\RouteTargetInterface';
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
                $targetClassName = $route->getTarget();
                if (is_subclass_of($targetClassName, self::ROUTE_TARGET_INTERFACE)) {

                    /** @var RouteTargetInterface $target */
                    $target = new $targetClassName();
                    $target->setRequest($request);

                    // If this instance already had argument, merge the matches with them.
                    $myArguments = $this->getArguments();
                    if (!is_null($myArguments)) {
                        $matches = array_merge($myArguments, $matches);
                    }
                    $target->setArguments($matches);

                    // If this instance already had a top-level router, pass it along.
                    // Otherwise, pass itself as the top-level router.
                    if (isset($this->router)) {
                        $target->setRouter($this->router);
                    } else {
                        $target->setRouter($this);
                    }

                    return $target->getResponse();

                } else {
                    return $this->getInternalServerErrorResponse($request);
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

    /** Prepare a response indicating a 500 Internal Server Error */
    protected function getInternalServerErrorResponse(RequestInterface $request)
    {
        $response = new Response(500);
        $response->body = 'Server error at ' . $request->getPath();
        return $response;
    }

}
