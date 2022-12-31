<?php

declare(strict_types=1);

namespace WellRESTed\Routing\Route;

use Psr\Http\Message\ServerRequestInterface;
use WellRESTed\Server;

class RouteMap
{
    private RouteFactory $routeFactory;

    /** @var array<string, Route> Map of targets to Routes */
    private array $routes = [];

    /** @var array<string, Route> Map of exact paths to Routes */
    private array $staticRoutes = [];

    /** @var array<string, Route> Map of path prefixes to Routes */
    private array $prefixRoutes = [];

    /** @var Route[] List array or Routes that match by pattern */
    private array $patternRoutes = [];

    public function __construct(Server $server)
    {
        $this->routeFactory = new RouteFactory($server);
    }

    public function getRoute(ServerRequestInterface $request): ?Route
    {
        $target = $this->getPath($request->getRequestTarget());

        return $this->getStaticRoute($target)
            ?? $this->getPrefixRoute($target)
            ?? $this->getPatternRoute($target)
            ?? null;
    }

    private function getPath(string $target): string
    {
        $queryStart = strpos($target, '?');
        if ($queryStart === false) {
            return $target;
        }
        return substr($target, 0, $queryStart);
    }

    private function getStaticRoute(string $target): ?Route
    {
        return $this->staticRoutes[$target] ?? null;
    }

    private function getPrefixRoute(string $target): ?Route
    {
        // Find all prefixes that match the start of this path.
        $prefixes = array_keys($this->prefixRoutes);
        $matches = array_filter(
            $prefixes,
            function ($prefix) use ($target) {
                return str_starts_with($target, $prefix);
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

    private function getPatternRoute(string $target): ?Route
    {
        foreach ($this->patternRoutes as $route) {
            if ($route->matchesRequestTarget($target)) {
                return $route;
            }
        }
        return null;
    }

    /**
     * @param string $method HTTP method(s) to match
     * @param string $target Request target or pattern to match
     * @param mixed $dispatchable Handler or middleware to dispatch
     *
     * @see DispatchedInterface::dispatch
     */
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

    /** @return array<string, Route> Map of targets to Routes */
    public function getRoutes(): array
    {
        return $this->routes;
    }
}
