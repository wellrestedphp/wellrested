<?php

/**
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

                $klass = $route->handler;
                $handler = new $klass($request, $matches);
                return $handler->response;

            }

        }

        return $this->getNoRouteResponse($request);
    }

    /**
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
