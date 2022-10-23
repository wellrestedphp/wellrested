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
    public function __construct(
        Server $server,
    ) {
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

        if ($route) {
            $request = $this->withPathVriables($request, $route);
            return $this->dispatch($route, $request, $response, $next);
        }

        if ($this->continueOnNotFound) {
            return $next($request, $response);
        }

        return $response->withStatus(404);
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
        $stack = [...$this->middleware, $route];
        $dispatcher = $this->getServer()->getDispatcher();
        return $dispatcher->dispatch(
            $stack,
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
