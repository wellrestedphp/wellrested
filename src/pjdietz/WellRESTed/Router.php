<?php

/**
 * pjdietz\WellRESTed\Router
 *
 * @author PJ Dietz <pj@pjdietz.com>
 * @copyright Copyright 2013 by PJ Dietz
 * @license MIT
 */

namespace pjdietz\WellRESTed;

use pjdietz\WellRESTed\Interfaces\DispatcherInterface;
use pjdietz\WellRESTed\Interfaces\ResponseInterface;
use pjdietz\WellRESTed\Interfaces\RoutableInterface;

/**
 * Router
 *
 * A Router uses a table of Routes to find the appropriate Handler for a request.
 */
class Router implements DispatcherInterface
{
    /** @var array  Array of Route objects */
    private $routes;

    /** Create a new Router. */
    public function __construct()
    {
        $this->routes = array();
    }

    /**
     * Return the response built by the handler based on the request
     *
     * @param RoutableInterface $request
     * @param null $args
     * @return ResponseInterface
     */
    public function getResponse(RoutableInterface $request, $args = null)
    {
        // Use the singleton if the caller did not pass a request.
        if (is_null($request)) {
            $request = Request::getRequest();
        }

        foreach ($this->routes as $route) {
            /** @var DispatcherInterface $route */
            $responce = $route->getResponse($request, $args);
            if ($responce) {
                return $responce;
            }
        }

        return $this->getNoRouteResponse($request);
    }

    /**
     * Append a new route to the route route table.
     *
     * @param DispatcherInterface $route
     */
    public function addRoute(DispatcherInterface $route)
    {
        $this->routes[] = $route;
    }

    /**
     * Append a series of routes.
     *
     * @param array $routes List array of DispatcherInterface instances
     */
    public function addRoutes(array $routes)
    {
        foreach ($routes as $route) {
            if ($route instanceof DispatcherInterface) {
                $this->addRoute($route);
            }
        }
    }

    public function respond() {
        $response = $this->getResponse(Request::getRequest());
        $response->respond();
    }

    /**
     * Prepare a resonse indicating a 404 Not Found error
     *
     * @param RoutableInterface $request
     * @return ResponseInterface
     */
    protected function getNoRouteResponse(RoutableInterface $request)
    {
        $response = new Response(404);
        $response->setBody('No resource at ' . $request->getPath());
        return $response;
    }

    /**
     * Prepare a response indicating a 500 Internal Server Error
     *
     * @param RoutableInterface $request
     * @param string $message Optional additional message.
     * @return ResponseInterface
     */
    protected function getInternalServerErrorResponse(RoutableInterface $request, $message = '')
    {
        $response = new Response(500);
        $response->setBody('Server error at ' . $request->getPath() . "\n" . $message);
        return $response;
    }

}
