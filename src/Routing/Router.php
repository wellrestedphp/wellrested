<?php

namespace WellRESTed\Routing;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WellRESTed\HttpExceptions\HttpException;
use WellRESTed\Routing\Route\RouteFactory;
use WellRESTed\Stream\StringStream;

class Router implements MiddlewareInterface
{
    /** @var array  Hash array of status code => error handler */
    private $statusHandlers;
    /** @var RouteTable Collection of routes */
    private $routeTable;
    /** @var RouteFactory */
    private $routeFactory;

    public function __construct()
    {
        $this->routeTable = new RouteTable();
        $this->routeFactory = new RouteFactory($this->routeTable);
        $this->statusHandlers = [];
    }

    /**
     * Create and return a route given a string path, a handler, and optional
     * extra arguments.
     *
     * The method will determine the most appropriate route subclass to use
     * and will forward the arguments on to the subclass's constructor.
     *
     * - Paths with no special characters will generate StaticRoutes
     * - Paths ending with * will generate PrefixRoutes
     * - Paths containing URI variables (e.g., {id}) will generate TemplateRoutes
     * - Regular exressions will generate RegexRoutes
     *
     * @param string $target Path, prefix, or pattern to match
     * @param mixed $middleware Middleware to dispatch
     * @param $defaultPattern @see TemplateRoute
     * @param $variablePatterns @see TemplateRoute
     */
    public function add($target, $middleware, $defaultPattern = null, $variablePatterns = null)
    {
        if (is_array($middleware)) {
            $middleware = $this->getMethodMap($middleware);
        }
        $this->routeFactory->registerRoute($target, $middleware, $defaultPattern, $variablePatterns);
    }

    public function setStatusHandler($statusCode, $middleware)
    {
        $this->statusHandlers[$statusCode] = $middleware;
    }

    public function dispatch(ServerRequestInterface $request, ResponseInterface &$response)
    {
        try {
            $this->routeTable->dispatch($request, $response);
        } catch (HttpException $e) {
            $response = $response->withStatus($e->getCode());
            $response = $response->withBody(new StringStream($e->getMessage()));
        }
        $statusCode = $response->getStatusCode();
        if (isset($this->statusHandlers[$statusCode])) {
            $middleware = $this->statusHandlers[$statusCode];
            $dispatcher = new Dispatcher();
            $dispatcher->dispatch($middleware, $request, $response);
        }
    }

    protected function getMethodMap(array $map)
    {
        return new MethodMap($map);
    }
}
