<?php

declare(strict_types=1);

namespace WellRESTed\Routing\Route;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use WellRESTed\Dispatching\DispatcherInterface;

class RouteMap
{
    private RouteFactory $routeFactory;

    /** @var Route[] Array of Route objects */
    private array $routes = [];

    /** @var array<string, Route> Hash array mapping exact paths to routes */
    private array $staticRoutes = [];

    /** @var array<string, Route> Hash array mapping path prefixes to routes */
    private $prefixRoutes = [];

    /** @var Route[] List array or routes that match by pattern */
    private $patternRoutes = [];

    public function __construct(DispatcherInterface $dispatcher)
    {
        $this->routeFactory = new RouteFactory($dispatcher);
    }

    public function getRoute(ServerRequestInterface $request): ?Route
    {
        $path = $this->getPath($request->getRequestTarget());

        $route = $this->getStaticRoute($path);
        if ($route) {
            return $route;
        }

        $route = $this->getPrefixRoute($path);
        if ($route) {
            return $route;
        }

        // Try each of the routes.
        foreach ($this->patternRoutes as $route) {
            if ($route->matchesRequestTarget($path)) {
                return $route;
            }
        }
        return null;
    }

    private function getPath(string $requestTarget): string
    {
        $queryStart = strpos($requestTarget, '?');
        if ($queryStart === false) {
            return $requestTarget;
        }
        return substr($requestTarget, 0, $queryStart);
    }

    private function getStaticRoute(string $requestTarget): ?Route
    {
        if (isset($this->staticRoutes[$requestTarget])) {
            return $this->staticRoutes[$requestTarget];
        }
        return null;
    }

    private function getPrefixRoute(string $requestTarget): ?Route
    {
        // Find all prefixes that match the start of this path.
        $prefixes = array_keys($this->prefixRoutes);
        $matches = array_filter(
            $prefixes,
            function ($prefix) use ($requestTarget) {
                return str_starts_with($requestTarget, $prefix);
            }
        );

        if (!$matches) {
            return null;
        }

        // If there are multiple matches, sort them to find the one with the
        // longest string length.
        if (count($matches) > 1) {
            $compareByLength = function (string $a, string $b): int {
                return strlen($b) - strlen($a);
            };
            usort($matches, $compareByLength);
        }

        $bestMatch = array_values($matches)[0];
        return $this->prefixRoutes[$bestMatch];
    }

    public function register(string $method, string $target, $dispatchable): void
    {
        $route = $this->getRouteForTarget($target);
        $route->register($method, $dispatchable);
    }

    private function getRouteForTarget(string $target): Route
    {
        if (isset($this->routes[$target])) {
            $route = $this->routes[$target];
        } else {
            $route = $this->routeFactory->create($target);
            $this->registerRouteForTarget($route, $target);
        }
        return $route;
    }

    private function registerRouteForTarget(Route $route, string $target): void
    {
        // Store the route to the hash indexed by original target.
        $this->routes[$target] = $route;

        // Store the route to the array of routes for its type.
        switch ($route->getType()) {
            case Route::TYPE_STATIC:
                $this->staticRoutes[$route->getTarget()] = $route;
                break;
            case Route::TYPE_PREFIX:
                $this->prefixRoutes[rtrim($route->getTarget(), '*')] = $route;
                break;
            case Route::TYPE_PATTERN:
                $this->patternRoutes[] = $route;
                break;
        }
    }
}
