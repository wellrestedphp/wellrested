<?php

/**
 * pjdietz\WellRESTed\RouteTable
 *
 * @author PJ Dietz <pj@pjdietz.com>
 * @copyright Copyright 2015 by PJ Dietz
 * @license MIT
 */

namespace pjdietz\WellRESTed;

use pjdietz\WellRESTed\Interfaces\HandlerInterface;
use pjdietz\WellRESTed\Interfaces\RequestInterface;
use pjdietz\WellRESTed\Interfaces\ResponseInterface;
use pjdietz\WellRESTed\Interfaces\Routes\PrefixRouteInterface;
use pjdietz\WellRESTed\Interfaces\Routes\StaticRouteInterface;

/**
 * RouteTable
 *
 * A RouteTable uses the request path to dispatche the best-matching handler.
 */
class RouteTable implements HandlerInterface
{
    /** @var array  Array of Route objects */
    private $routes;
    /** @var array  Hash array mapping exact paths to routes */
    private $staticRoutes;
    /** @var array  Hash array mapping path prefixes to routes */
    private $prefixRoutes;

    /** Create a new RouteTable */
    public function __construct()
    {
        $this->routes = array();
        $this->prefixRoutes = array();
        $this->staticRoutes = array();
    }

    /**
     * Return the response from the best matching route.
     *
     * @param RequestInterface $request
     * @param array|null $args
     * @return ResponseInterface
     */
    public function getResponse(RequestInterface $request, array $args = null)
    {
        $response = null;

        // First check if there is a static route.
        $response = $this->getStaticResponse($request, $args);
        if ($response) {
            return $response;
        }

        // Check prefix routes for any routes that match. Use the longest matching prefix.
        $response = $this->getPrefixResponse($request, $args);
        if ($response) {
            return $response;
        }

        // Try each of the routes.
        foreach ($this->routes as $route) {
            /** @var HandlerInterface $route */
            $response = $route->getResponse($request, $args);
            if ($response) {
                return $response;
            }
        }

        return null;
    }

    /**
     * Return the response associated with the matching static route, or null if none match.
     *
     * @param RequestInterface $request
     * @param array|null $args
     * @return ResponseInterface
     */
    private function getStaticResponse(RequestInterface $request, array $args = null)
    {
        $path = $request->getPath();
        if (isset($this->staticRoutes[$path])) {
            $route = $this->staticRoutes[$path];
            return $route->getResponse($request, $args);
        }
        return null;
    }

    /**
     * Returning the best-matching prefix handler, or null if none match.
     *
     * @param RequestInterface $request
     * @param array|null $args
     * @return ResponseInterface
     */
    private function getPrefixResponse(RequestInterface $request, array $args = null)
    {
        $path = $request->getPath();

        // Find all prefixes that match the start of this path.
        $prefixes = array_keys($this->prefixRoutes);
        $matches = array_filter(
            $prefixes,
            function ($prefix) use ($path) {
                return (strrpos($path, $prefix, -strlen($path)) !== false);
            }
        );

        if ($matches) {
            if (count($matches) > 0) {
                // If there are multiple matches, sort them to find the one with the longest string length.
                $compareByLength = function ($a, $b) {
                    return strlen($b) - strlen($a);
                };
                usort($matches, $compareByLength);
            }
            $route = $this->prefixRoutes[$matches[0]];
            return $route->getResponse($request, $args);
        }
        return null;
    }

    /**
     * Append a new route.
     *
     * @param HandlerInterface $route
     */
    public function addRoute(HandlerInterface $route)
    {
        if ($route instanceof StaticRouteInterface) {
            $this->addStaticRoute($route);
        } elseif ($route instanceof PrefixRouteInterface) {
            $this->addPrefixRoute($route);
        } else {
            $this->routes[] = $route;
        }
    }

    /**
     * Register a new static route.
     *
     * @param StaticRouteInterface $staticRoute
     */
    private function addStaticRoute(StaticRouteInterface $staticRoute)
    {
        foreach ($staticRoute->getPaths() as $path) {
            $this->staticRoutes[$path] = $staticRoute;
        }
    }

    /**
     * Register a new prefix route.
     *
     * @param PrefixRouteInterface $prefixRoute
     */
    private function addPrefixRoute(PrefixRouteInterface $prefixRoute)
    {
        foreach ($prefixRoute->getPrefixes() as $prefix) {
            $this->prefixRoutes[$prefix] = $prefixRoute;
        }
    }
}
