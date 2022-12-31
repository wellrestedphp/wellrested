<?php

declare(strict_types=1);

namespace WellRESTed\Routing;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WellRESTed\MiddlewareInterface;
use WellRESTed\Routing\Route\Route;
use WellRESTed\Routing\Route\RouteMap;
use WellRESTed\Server;
use WellRESTed\ServerReferenceTrait;

class Router implements MiddlewareInterface
{
    use ServerReferenceTrait;

    private RouteMap $routeMap;

    /** @var mixed[] List array of middleware */
    private array $middleware;

    /** @var bool Call the next middleware when no route matches */
    private bool $continueOnNotFound = false;

    /**
     * Create a new Router.
     *
     * Use Server::createRouter to instantiate a new Router rather than calling
     * this constructor directly.
     *
     * @param Server $server
     *     The server that created this instance.
     */
    public function __construct(Server $server)
    {
        $this->setServer($server);
        $this->routeMap = new RouteMap($server);
        $this->middleware = [];
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable $next
     * @return ResponseInterface
     */
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        $next
    ): ResponseInterface {
        $route = $this->routeMap->getRoute($request);

        if (!$route) {
            $result = $this->getTrailingSlashRoute($request, $response);
            if ($result instanceof Route) {
                $route = $result;
            } elseif ($result instanceof ResponseInterface) {
                return $result;
            }
        }

        if ($route) {
            $request = $this->withPathVriables($request, $route);
            return $this->dispatch($route, $request, $response, $next);
        }

        if ($this->continueOnNotFound) {
            return $next($request, $response);
        }

        return $response->withStatus(404);
    }

    /**
     * Retry a request with an added trailing slash.
     *
     * The return type varies based on the traliling slash mode.
     *   - null: STRICT (or adding a traliling slash does not match a route)
     *   - Route: LOOSE
     *   - ResponseInterface: REDIRECT
     */
    private function getTrailingSlashRoute(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): Route|ResponseInterface|null {
        $mode = $this->getServer()->getTrailingSlashMode();
        if ($mode === TrailingSlashMode::STRICT) {
            return null;
        }

        $slashRequest = $this->getTrailingSlashRequest($request);

        $slashRoute = $this->routeMap->getRoute($slashRequest);
        if (!$slashRoute) {
            return null;
        }

        return match ($mode) {
            TrailingSlashMode::STRICT => null,
            TrailingSlashMode::LOOSE => $slashRoute,
            TrailingSlashMode::REDIRECT => $this->getRedirectResponse(
                $response,
                $slashRequest->getRequestTarget()
            ),
        };
    }

    private function getTrailingSlashRequest(
        ServerRequestInterface $request
    ): ServerRequestInterface {
        $path = $request->getRequestTarget();
        $query = '';
        if (str_contains($path, '?')) {
            [$path, $query] = explode('?', $path);
        }

        $retryTarget = (str_ends_with($path, '/'))
            ? substr($path, 0, -1)
            : $path . '/';

        if ($query) {
            $retryTarget .= '?' . $query;
        }

        return $request->withRequestTarget($retryTarget);
    }

    private function getRedirectResponse(
        ResponseInterface $response,
        string $location
    ): ResponseInterface {
        return $response
            ->withStatus(301)
            ->withHeader('Location', $location);
    }

    private function withPathVriables(
        ServerRequestInterface $request,
        Route $route
    ): ServerRequestInterface {
        $vars = $route->getPathVariables();
        $name = $this->getServer()->getPathVariablesAttributeName();
        if ($name) {
            $request = $request->withAttribute($name, $vars);
        } else {
            foreach ($vars as $name => $value) {
                $request = $request->withAttribute($name, $value);
            }
        }
        return $request;
    }

    private function dispatch(
        callable $route,
        ServerRequestInterface $request,
        ResponseInterface $response,
        callable $next
    ): ResponseInterface {
        if (!$this->middleware) {
            return $route($request, $response, $next);
        }
        $dispatchables = [...$this->middleware, $route];
        $dispatcher = $this->getServer()->getDispatcher();
        return $dispatcher->dispatch(
            $dispatchables,
            $request,
            $response,
            $next
        );
    }

    /**
     * Register handlers and middleware with the router for a given path and
     * method.
     *
     * $method may be:
     * - A single verb ("GET"),
     * - A comma-separated list of verbs ("GET,PUT,DELETE")
     * - "*" to indicate any method.
     *
     * $target may be:
     * - An exact path (e.g., "/path/")
     * - A prefix path ending with "*"" ("/path/*"")
     * - A URI template with variables enclosed in "{}" ("/path/{id}")
     * - A regular expression ("~/cat/([0-9]+)~")
     *
     * $dispatchable may be:
     * - An instance implementing one of these interfaces:
     *     - Psr\Http\Server\RequestHandlerInterface
     *     - Psr\Http\Server\MiddlewareInterface
     *     - WellRESTed\MiddlewareInterface
     *     - Psr\Http\Message\ResponseInterface
     * - A string matching the name of a service in the depdency container.
     * - A string containing the fully qualified class name of a class
     *     implementing one of the interfaces listed above.
     * - A callable that returns an instance implementing one of the
     *     interfaces listed above.
     * - A callable with a signature matching the signature of
     *     WellRESTed\MiddlewareInterface::__invoke
     * - An array containing any of the items in this list.
     * @see DispatchedInterface::dispatch
     *
     * @param string $method HTTP method(s) to match
     * @param string $target Request target or pattern to match
     * @param mixed $dispatchable Handler or middleware to dispatch
     * @return static
     */
    public function register(string $method, string $target, $dispatchable): Router
    {
        $this->routeMap->register($method, $target, $dispatchable);
        return $this;
    }

    /** @return array<string, Route> Map of routes by target */
    public function getRoutes(): array
    {
        return $this->routeMap->getRoutes();
    }

    /**
     * Push a new middleware onto the stack.
     *
     * Middleware for a router runs before the middleware and handler for the
     * matched route and runs only when a route matched.
     *
     * $middleware may be:
     * - An instance implementing MiddlewareInterface
     * - A string containing the fully qualified class name of a class
     *     implementing MiddlewareInterface
     * - A callable that returns an instance implementing MiddleInterface
     * - A callable matching the signature of MiddlewareInterface::dispatch
     * @see DispatchedInterface::dispatch
     *
     * @param mixed $middleware Middleware to dispatch in sequence
     * @return static
     */
    public function add($middleware): Router
    {
        $this->middleware[] = $middleware;
        return $this;
    }

    /** @return mixed[] Middleware to run before the matched route */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * Configure the instance to delegate to the next middleware when no route
     * matches.
     *
     * @return static
     */
    public function continueOnNotFound(): Router
    {
        $this->continueOnNotFound = true;
        return $this;
    }
}
