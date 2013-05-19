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
     * @param Route $route
     */
    public function addRoute(Route $route)
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
            if (preg_match($route->pattern, $path, $matches)) {
                if (is_subclass_of($route->handler, '\pjdietz\WellRESTed\Handler')) {
                    $handler = new $route->handler($request, $matches);
                    return $handler->response;
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
