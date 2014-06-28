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
use pjdietz\WellRESTed\Interfaces\RouteTargetInterface;

/**
 * Router
 *
 * A Router uses a table of Routes to find the appropriate Handler for a request.
 */
class Router extends RouteTarget implements DispatcherInterface
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
     * @return ResponseInterface
     */
    public function getResponse(RoutableInterface $request = null)
    {
        // Use the singleton if the caller did not pass a request.
        if (is_null($reqs))

        // Set the instance's request, if the called passed one.
        if (!is_null($request)) {
            $this->request = $request;
        }

        // If the instance does not have a request, use the singleton.
        if (is_null($this->request)) {
            $this->request = Request::getRequest();
        }

        // Reference the request and path.
        $request = $this->request;
        $request->incrementRouteDepth();
        $path = $request->getPath();

        if ($request->getRouteDepth() >= $this->getMaxDepth()) {
            return $this->getInternalServerErrorResponse($request, 'Maximum route recursion reached.');
        }

        foreach ($this->routes as $route) {
            /** @var RouteInterface $route */
            if (preg_match($route->getPattern(), $path, $matches)) {
                $targetClassName = $route->getTarget();
                if (is_subclass_of($targetClassName, self::ROUTE_TARGET_INTERFACE)) {

                    /** @var RouteTargetInterface $target */
                    $target = new $targetClassName();
                    $target->setRequest($request);

                    // If this instance already had arguments, merge the matches with them.
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
     * Append a new Route instance to the Router's route table.
     *
     * @param RouteInterface $route
     */
    public function addRoute(DispatcherInterface $route)
    {
        $this->routes[] = $route;
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
        $response->body = 'No resource at ' . $request->getPath();
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
