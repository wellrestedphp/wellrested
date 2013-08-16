<?php

/**
 * pjdietz\WellRESTed\Router
 *
 * @author PJ Dietz <pj@pjdietz.com>
 * @copyright Copyright 2013 by PJ Dietz
 * @license MIT
 */

namespace pjdietz\WellRESTed;

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
    /** @var int  Default maximum number of levels of routing. */
    const MAX_DEPTH = 10;
    /** @var int maximum levels of routing before the router raises an error. */
    protected $maxDepth = self::MAX_DEPTH;
    /** @var array  Array of Route objects */
    private $routes;
    /** @var int counter incrememted each time a router dispatches a route target. */
    private $depth = 0;

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
                        $this->router->incrementDepth();
                        $target->setRouter($this->router);
                    } else {
                        $this->incrementDepth();
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

    public function incrementDepth()
    {
        $this->depth++;
        if ($this->depth >= $this->maxDepth) {
            $response = $this->getInternalServerErrorResponse(
                $this->getRequest(),
                'Maximum recursion level reached.'
            );
            $response->respond();
            exit;
        }
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
    protected function getInternalServerErrorResponse(RequestInterface $request, $message = '')
    {
        $response = new Response(500);
        $response->setBody('Server error at ' . $request->getPath() . "\n" . $message);
        return $response;
    }

}
