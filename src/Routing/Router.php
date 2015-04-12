<?php

namespace WellRESTed\Routing;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WellRESTed\HttpExceptions\HttpException;
use WellRESTed\Message\Response;
use WellRESTed\Message\ServerRequest;
use WellRESTed\Routing\Route\RouteFactory;
use WellRESTed\Routing\Route\RouteFactoryInterface;
use WellRESTed\Stream\StringStream;

class Router implements MiddlewareInterface
{
    /** @var array  Hash array of status code => error handler */
    private $statusHandlers;
    /** @var RouteTable Collection of routes */
    private $routeTable;
    /** @var RouteFactoryInterface */
    private $routeFactory;

    public function __construct()
    {
        $this->routeFactory = $this->getRouteFactory();
        $this->routeTable = $this->getRouteTable();
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
     * @param mixed $extra
     */
    public function add($target, $middleware, $extra = null)
    {
        if (is_array($middleware)) {
            $map = $this->getMethodMap();
            $map->addMap($middleware);
            $middleware = $map;
        }
        $this->routeFactory->registerRoute($this->routeTable, $target, $middleware, $extra);
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
            $dispatcher = $this->getDispatcher();
            $dispatcher->dispatch($middleware, $request, $response);
        }
    }

    public function respond()
    {
        $request = $this->getRequest();
        $response = $this->getResponse();
        $this->dispatch($request, $response);
        $responder = $this->getResponder();
        $responder->respond($response);
    }

    // ------------------------------------------------------------------------
    // The following methods provide instaces the router will use. Override
    // to provide custom classes or configured instances.

    // @codeCoverageIgnoreStart

    /**
     * Return an instance that can dispatch middleware.
     * Override to provide a custom class.
     *
     * @return DispatcherInterface
     */
    protected function getDispatcher()
    {
        return new Dispatcher();
    }

    /**
     * @return MethodMapInterface
     */
    protected function getMethodMap()
    {
        return new MethodMap();
    }

    /**
     * @return ServerRequestInterface
     */
    protected function getRequest()
    {
        return ServerRequest::getServerRequest();
    }

    /**
     * @return ResponderInterface
     */
    protected function getResponder()
    {
        return new Responder();
    }

    /**
     * @return ResponseInterface
     */
    protected function getResponse()
    {
        return new Response();
    }

    /**
     * @return RouteFactoryInterface
     */
    protected function getRouteFactory()
    {
        return new RouteFactory();
    }

    /**
     * @return RouteTableInterface
     */
    protected function getRouteTable()
    {
        return new RouteTable();
    }

    // @codeCoverageIgnoreEnd
}
