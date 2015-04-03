<?php

namespace WellRESTed\Routing;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WellRESTed\Routing\Route\PrefixRouteInterface;
use WellRESTed\Routing\Route\RouteInterface;
use WellRESTed\Routing\Route\StaticRouteInterface;

class RouteTable implements MiddlewareInterface, RouteTableInterface
{
    /** @var RouteInterface[]  Array of Route objects */
    private $routes;
    /** @var array  Hash array mapping exact paths to routes */
    private $staticRoutes;
    /** @var array  Hash array mapping path prefixes to routes */
    private $prefixRoutes;

    public function __construct()
    {
        $this->routes = [];
        $this->staticRoutes = [];
        $this->prefixRoutes = [];
    }

    public function addRoute(RouteInterface $route)
    {
        $this->routes[] = $route;
    }

    public function addStaticRoute(StaticRouteInterface $staticRoute)
    {
        $this->staticRoutes[$staticRoute->getPath()] = $staticRoute;
    }

    public function addPrefixRoute(PrefixRouteInterface $prefxRoute)
    {
        $this->prefixRoutes[$prefxRoute->getPrefix()] = $prefxRoute;
    }

    public function dispatch(ServerRequestInterface $request, ResponseInterface &$response)
    {
        $requestTarget = $request->getRequestTarget();

        $route = $this->getStaticRoute($requestTarget);
        if ($route) {
            $route->dispatch($request, $response);
            return;
        }

        $route = $this->getPrefixRoute($requestTarget);
        if ($route) {
            $route->dispatch($request, $response);
            return;
        }

        // Try each of the routes.
        foreach ($this->routes as $route) {
            if ($route->matchesRequestTarget($requestTarget, $captures)) {
                if (is_array($captures)) {
                    foreach ($captures as $key => $value) {
                        $request = $request->withAttribute($key, $value);
                    }
                }
                $route->dispatch($request, $response);
            }
        }
    }

    private function getStaticRoute($requestTarget)
    {
        if (isset($this->staticRoutes[$requestTarget])) {
            return $this->staticRoutes[$requestTarget];
        }
        return null;
    }

    private function getPrefixRoute($requestTarget)
    {
        // Find all prefixes that match the start of this path.
        $prefixes = array_keys($this->prefixRoutes);
        $matches = array_filter(
            $prefixes,
            function ($prefix) use ($requestTarget) {
                return (strrpos($requestTarget, $prefix, -strlen($requestTarget)) !== false);
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
            return $route;
        }
        return null;
    }
}
