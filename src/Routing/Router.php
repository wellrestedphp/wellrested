<?php

namespace WellRESTed\Routing;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WellRESTed\HttpExceptions\HttpException;
use WellRESTed\Message\Response;
use WellRESTed\Message\ServerRequest;
use WellRESTed\Message\Stream;

class Router implements MiddlewareInterface, RouteMapInterface
{
    /** @var DispatcherInterface */
    private $dispatcher;

    /** @var mixed[] List of middleware to dispatch immediately before concluding the request-response cycle. */
    private $finalizationHooks;

    /** @var mixed[] List of middleware to dispatch after the router dispatches the matched route. */
    private $postRouteHooks;

    /** @var mixed[] List of middleware to dispatch before the router dispatches the matched route. */
    private $preRouteHooks;

    /** @var array Hash array of status code => middleware */
    private $statusHooks;

    /** @var RouteMapInterface */
    private $routeMap;

    // ------------------------------------------------------------------------

    public function __construct()
    {
        $this->dispatcher = $this->getDispatcher();
        $this->finalizationHooks = $this->getFinalizationHooks();
        $this->postRouteHooks = $this->getPostRouteHooks();
        $this->preRouteHooks = $this->getPreRouteHooks();
        $this->statusHooks = $this->getStatusHooks();
        $this->routeMap = $this->getRouteMap();
    }

    // ------------------------------------------------------------------------
    // MiddlewareInterface

    public function dispatch(ServerRequestInterface $request, ResponseInterface &$response)
    {
        $this->dispatchPreRouteHooks($request, $response);
        try {
            $this->routeMap->dispatch($request, $response);
        } catch (HttpException $e) {
            $response = $response->withStatus($e->getCode());
            $response = $response->withBody(new Stream($e->getMessage()));
        }
        $this->dispatchStatusHooks($request, $response);
        $this->dispatchPostRouteHooks($request, $response);
        $this->dispatchFinalizationHooks($request, $response);
    }

    // ------------------------------------------------------------------------
    // RouteMapInterface

    /**
     * Register middleware with the router for a given path and method.
     *
     * $method may be:
     * - A single verb ("GET"),
     * - A comma-separated list of verbs ("GET,PUT,DELETE")
     * - "*" to indicate any method.
     * @see MethodMapInterface::setMethod
     *
     * $target may be:
     * - An exact path (e.g., "/path/")
     * - An prefix path ending with "*"" ("/path/*"")
     * - A URI template with variables enclosed in "{}" ("/path/{id}")
     * - A regular expression ("~/cat/([0-9]+)~")
     *
     * $middleware may be:
     * - An instance implementing MiddlewareInterface
     * - A string containing the fully qualified class name of a class
     *     implementing MiddlewareInterface
     * - A callable that returns an instance implementing MiddleInterface
     * - A callable maching the signature of MiddlewareInteraface::dispatch
     * @see DispatchedInterface::dispatch
     *
     * @param string $target Request target or pattern to match
     * @param string $method HTTP method(s) to match
     * @param mixed $middleware Middleware to dispatch
     */
    public function add($method, $target, $middleware)
    {
        $this->routeMap->add($method, $target, $middleware);
    }

    // ------------------------------------------------------------------------

    public function respond()
    {
        $request = $this->getRequest();
        $response = $this->getResponse();
        $this->dispatch($request, $response);
        $responder = $this->getResponder();
        $responder->respond($response);
    }

    // ------------------------------------------------------------------------
    // Hooks

    public function addPreRouteHook($middleware)
    {
        $this->preRouteHooks[] = $middleware;
    }

    public function addPostRouteHook($middleware)
    {
        $this->postRouteHooks[] = $middleware;
    }

    public function addFinalizationHook($middleware)
    {
        $this->finalizationHooks[] = $middleware;
    }

    public function setStatusHook($statusCode, $middleware)
    {
        $this->statusHooks[$statusCode] = $middleware;
    }

    // ------------------------------------------------------------------------
    // The following methods provide instaces the router will use. Override
    // to provide custom classes or configured instances.

    /**
     * Return an instance that can dispatch middleware.
     *
     * Override to provide a custom class.
     *
     * @return DispatcherInterface
     */
    protected function getDispatcher()
    {
        return new Dispatcher();
    }

    /**
     * Return an instance that maps routes to middleware.
     *
     * Override to provide a custom class.
     *
     * @return RouteMapInterface
     */
    protected function getRouteMap()
    {
        return new RouteMap();
    }

    /**
     * @return array
     */
    protected function getPreRouteHooks()
    {
        return [];
    }

    /**
     * @return array
     */
    protected function getPostRouteHooks()
    {
        return [];
    }

    /**
     * @return array
     */
    protected function getStatusHooks()
    {
        return [];
    }

    /**
     * @return array
     */
    protected function getFinalizationHooks()
    {
        return [];
    }

    // @codeCoverageIgnoreStart

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

    // @codeCoverageIgnoreEnd

    // ------------------------------------------------------------------------

    private function dispatchPreRouteHooks(ServerRequestInterface $request, ResponseInterface &$response)
    {
        foreach ($this->preRouteHooks as $hook) {
            $this->dispatcher->dispatch($hook, $request, $response);
        }
    }

    private function dispatchPostRouteHooks(ServerRequestInterface $request, ResponseInterface &$response)
    {
        foreach ($this->postRouteHooks as $hook) {
            $this->dispatcher->dispatch($hook, $request, $response);
        }
    }

    private function dispatchFinalizationHooks(ServerRequestInterface $request, ResponseInterface &$response)
    {
        foreach ($this->finalizationHooks as $hook) {
            $this->dispatcher->dispatch($hook, $request, $response);
        }
    }

    private function dispatchStatusHooks(ServerRequestInterface $request, ResponseInterface &$response)
    {
        $statusCode = $response->getStatusCode();
        if (isset($this->statusHooks[$statusCode])) {
            $middleware = $this->statusHooks[$statusCode];
            $dispatcher = $this->getDispatcher();
            $dispatcher->dispatch($middleware, $request, $response);
        }
    }
}
