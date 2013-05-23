<?php

/**
 * pjdietz\WellRESTed\Router
 *
 * @author PJ Dietz <pj@pjdietz.com>
 * @copyright Copyright 2013 by PJ Dietz
 * @license MIT
 */

namespace pjdietz\WellRESTed;

/**
 * Router
 *
 * A Router uses a table of Routes to find the appropriate Handler for a
 * request.
 */
class Router
{
    /**
     * Array of Route objects
     *
     * @var array
     */
    protected $routes;

    /**
     * Create a new Router.
     */
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
     * Return the Response built by the Handler based on the Request
     *
     * @param Request $request
     * @return Response
     */
    public function getResponse($request = null)
    {
        if (is_null($request)) {
            $request = Request::getRequest();
        }

        $path = $request->path;

        foreach ($this->routes as $route) {
            /** @var RouteInterface $route */
            if (preg_match($route->getPattern(), $path, $matches)) {
                $handlerClassName = $route->getHandler();
                if (is_subclass_of($handlerClassName, '\pjdietz\WellRESTed\HandlerInterface')) {
                    /** @var HandlerInterface $handler */
                    $handler = new $handlerClassName();
                    $handler->setRequest($request);
                    $handler->setArguments($matches);
                    return $handler->getResponse();
                } else {
                    return $this->getNoRouteResponse($request);
                }
            }
        }

        return $this->getNoRouteResponse($request);
    }

    /**
     * Prepare a Resonse indicating a 404 Not Found error
     *
     * @param Request $request
     * @return Response
     */
    protected function getNoRouteResponse(Request $request)
    {
        $response = new Response(404);
        $response->body = 'No resource at ' . $request->uri;
        return $response;
    }

}
