<?php

namespace WellRESTed\Routing;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class MethodMap implements MiddlewareInterface
{
    protected $map;

    /**
     * @param array $map
     */
    public function __construct(array $map = null)
    {
        $this->map = [];
        if ($map) {
            foreach ($map as $method => $middleware) {
                $this->add($method, $middleware);
            }
        }
    }

    /**
     * @param string $method
     * @param mixed $middleware
     */
    public function add($method, $middleware)
    {
        $method = strtoupper($method);
        $methods = explode(",", $method);
        $methods = array_map("trim", $methods);
        foreach ($methods as $method) {
            $this->map[$method] = $middleware;
        }
    }

    public function dispatch(ServerRequestInterface $request, ResponseInterface &$response)
    {
        $method = strtoupper($request->getMethod());
        // Dispatch middleware registered with the explicitly matching method.
        if (isset($this->map[$method])) {
            $middleware = $this->map[$method];
            $this->disptchMiddleware($middleware, $request, $response);
            return;
        }
        // For HEAD, dispatch GET by default.
        if ($method === "HEAD" && isset($this->map["GET"])) {
            $middleware = $this->map["GET"];
            $this->disptchMiddleware($middleware, $request, $response);
            return;
        }
        // Method is not defined. Respond describing the allowed methods,
        // either as a 405 response or in response to an OPTIONS request.
        if ($method === "OPTIONS") {
            $response = $response->withStatus(200);
        } else {
            $response = $response->withStatus(405);
        }
        $this->addAllowHeader($response);
    }

    protected function addAllowHeader(ResponseInterface &$response)
    {
        $methods = join(",", $this->getAllowedMethods());
        $response = $response->withHeader("Allow", $methods);
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
     * Return an instance that can dispatch middleware. Uses Dispatcher by default.
     * Override to provide a custom class.
     */
    protected function getDispatcher()
    {
        return new Dispatcher();
    }

    private function disptchMiddleware($middleware, ServerRequestInterface $request, ResponseInterface &$response)
    {
        $dispatcher = $this->getDispatcher();
        $dispatcher->dispatch($middleware, $request, $response);
    }
}
