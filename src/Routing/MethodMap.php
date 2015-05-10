<?php

namespace WellRESTed\Routing;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WellRESTed\Dispatching\Dispatcher;
use WellRESTed\Dispatching\DispatcherInterface;

class MethodMap implements MiddlewareInterface, MethodMapInterface
{
    protected $map;

    // ------------------------------------------------------------------------

    public function __construct()
    {
        $this->map = [];
    }

    // ------------------------------------------------------------------------
    // MethodMapInterface

    /**
     * Register middleware with a method.
     *
     * $method may be:
     * - A single verb ("GET"),
     * - A comma-separated list of verbs ("GET,PUT,DELETE")
     * - "*" to indicate any method.
     *
     * $middleware may be:
     * - An instance implementing MiddlewareInterface
     * - A string containing the fully qualified class name of a class
     *     implementing MiddlewareInterface
     * - A callable that returns an instance implementing MiddleInterface
     * - A callable maching the signature of MiddlewareInteraface::dispatch
     * @see DispatchedInterface::dispatch
     *
     * $middleware may also be null, in which case any previously set
     * middleware for that method or methods will be unset.
     *
     * @param string $method
     * @param mixed $middleware
     */
    public function register($method, $middleware)
    {
        $methods = explode(",", $method);
        $methods = array_map("trim", $methods);
        foreach ($methods as $method) {
            $this->map[$method] = $middleware;
        }
    }

    // ------------------------------------------------------------------------
    // MiddlewareInterface

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable $next
     * @return ResponseInterface
     */
    public function dispatch(ServerRequestInterface $request, ResponseInterface $response, $next)
    {
        $method = $request->getMethod();
        // Dispatch middleware registered with the explicitly matching method.
        if (isset($this->map[$method])) {
            $middleware = $this->map[$method];
            return $this->dispatchMiddleware($middleware, $request, $response, $next);
        }
        // For HEAD, dispatch GET by default.
        if ($method === "HEAD" && isset($this->map["GET"])) {
            $middleware = $this->map["GET"];
            return $this->dispatchMiddleware($middleware, $request, $response, $next);
        }
        // Dispatch * middleware, if registered.
        if (isset($this->map["*"])) {
            $middleware = $this->map["*"];
            return $this->dispatchMiddleware($middleware, $request, $response, $next);
        }
        // Respond describing the allowed methods, either as a 405 response or
        // in response to an OPTIONS request.
        if ($method === "OPTIONS") {
            $response = $response->withStatus(200);
        } else {
            $response = $response->withStatus(405);
        }
        return $next($request, $this->addAllowHeader($response));
    }

    // ------------------------------------------------------------------------

    protected function addAllowHeader(ResponseInterface $response)
    {
        $methods = join(",", $this->getAllowedMethods());
        return $response->withHeader("Allow", $methods);
    }

    protected function getAllowedMethods()
    {
        $methods = array_keys($this->map);
        // Add HEAD if GET is allowed and HEAD is not present.
        if (in_array("GET", $methods) && !in_array("HEAD", $methods)) {
            $methods[] = "HEAD";
        }
        // Add OPTIONS if not already present.
        if (!in_array("OPTIONS", $methods)) {
            $methods[] = "OPTIONS";
        }
        return $methods;
    }

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
     * @param $middleware
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param $next
     * @return ResponseInterface
     */
    private function dispatchMiddleware($middleware, ServerRequestInterface $request, ResponseInterface &$response, $next)
    {
        $dispatcher = $this->getDispatcher();
        return $dispatcher->dispatch($middleware, $request, $response, $next);
    }
}
