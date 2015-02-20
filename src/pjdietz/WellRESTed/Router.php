<?php

/**
 * pjdietz\WellRESTed\Router
 *
 * @author PJ Dietz <pj@pjdietz.com>
 * @copyright Copyright 2015 by PJ Dietz
 * @license MIT
 */

namespace pjdietz\WellRESTed;

use pjdietz\WellRESTed\Exceptions\HttpExceptions\HttpException;
use pjdietz\WellRESTed\Interfaces\HandlerInterface;
use pjdietz\WellRESTed\Interfaces\RequestInterface;
use pjdietz\WellRESTed\Interfaces\ResponseInterface;
use pjdietz\WellRESTed\Interfaces\Routes\PrefixRouteInterface;
use pjdietz\WellRESTed\Interfaces\Routes\StaticRouteInterface;
use pjdietz\WellRESTed\Routes\PrefixRoute;
use pjdietz\WellRESTed\Routes\StaticRoute;

/**
 * Router
 *
 * A Router uses a table of Routes to find the appropriate Handler for a request.
 */
class Router implements HandlerInterface
{
    /** @var array  Array of Route objects */
    private $routes;

    /** @var array  Hash array mapping path prefixes to routes */
    private $prefixRoutes;

    /** @var array  Hash array mapping exact paths to routes */
    private $staticRoutes;

    /** @var array  Hash array of status code => error handler */
    private $errorHandlers;

    /** Create a new Router. */
    public function __construct()
    {
        $this->routes = array();
        $this->prefixRoutes = array();
        $this->staticRoutes = array();
        $this->errorHandlers = array();
    }

    /**
     * Return the response built by the handler based on the request
     *
     * @param RequestInterface $request
     * @param array|null $args
     * @return ResponseInterface
     */
    public function getResponse(RequestInterface $request, array $args = null)
    {
        $response = $this->getResponseFromRoutes($request, $args);
        if ($response) {
            // Check if the router has an error handler for this status code.
            $status = $response->getStatusCode();
            $errorResponse = $this->getErrorResponse($status, $request, $args, $response);
            if ($errorResponse) {
                return $errorResponse;
            }
        }
        return $response;
    }

    /**
     * Append a new route to the route table.
     *
     * @param HandlerInterface $route
     */
    public function addRoute(HandlerInterface $route)
    {
        if ($route instanceof StaticRouteInterface) {
            $this->addStaticRoute($route);
        } elseif ($route instanceof PrefixRouteInterface) {
            $this->addPrefixRoute($route);
        } else {
            $this->routes[] = $route;
        }
    }

    /**
     * Append a series of routes.
     *
     * @param array $routes List array of HandlerInterface instances
     */
    public function addRoutes(array $routes)
    {
        foreach ($routes as $route) {
            if ($route instanceof HandlerInterface) {
                $this->addRoute($route);
            }
        }
    }

    /**
     * Add a custom error handler.
     *
     * @param integer $statusCode The error status code
     * @param callable|string|HandlerInterface $errorHandler
     */
    public function setErrorHandler($statusCode, $errorHandler)
    {
        $this->errorHandlers[$statusCode] = $errorHandler;
    }

    /**
     * Add custom error handlers.
     *
     * @param array $errorHandlers Array mapping integer error codes to handlers
     */
    public function setErrorHandlers(array $errorHandlers)
    {
        foreach ($errorHandlers as $statusCode => $errorHandler) {
            $this->setErrorHandler($statusCode, $errorHandler);
        }
    }

    /**
     * Dispatch the singleton Request through the router and output the response.
     *
     * Respond with a 404 Not Found if no route provides a response.
     * @param array|null $args
     */
    public function respond($args = null)
    {
        $request = Request::getRequest();
        $response = $this->getResponse($request, $args);
        if (!$response) {
            $response = $this->getNoRouteResponse($request);
        }
        $response->respond();
    }

    /**
     * Prepare a response indicating a 404 Not Found error
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     */
    protected function getNoRouteResponse(RequestInterface $request)
    {
        $response = $this->getErrorResponse(404, $request);
        if ($response) {
            return $response;
        }

        $response = new Response(404);
        $response->setBody('No resource at ' . $request->getPath());
        return $response;
    }

    private function addStaticRoute(StaticRouteInterface $staticRoute)
    {
        foreach ($staticRoute->getPaths() as $path) {
            $this->staticRoutes[$path] = $staticRoute;
        }
    }

    private function addPrefixRoute(PrefixRouteInterface $prefixRoute)
    {
        foreach ($prefixRoute->getPrefixes() as $prefix) {
            $this->prefixRoutes[$prefix] = $prefixRoute;
        }
    }

    private function getErrorResponse($status, $request, $args = null, $response = null)
    {
        if (isset($this->errorHandlers[$status])) {
            $unpacker = new HandlerUnpacker();
            $errorHandler = $unpacker->unpack($this->errorHandlers[$status]);
            // Pass the response triggering this along to the error handler.
            $errorArgs = array("response" => $response);
            if ($args) {
                $errorArgs = array_merge($args, $errorArgs);
            }
            return $errorHandler->getResponse($request, $errorArgs);
        }
        return null;
    }

    /**
     * Returning the handler associated with the matching static route, or null if none match.
     *
     * @param $path string The request's path
     * @return HandlerInterface|null
     */
    private function getStaticHandler($path)
    {
        if (isset($this->staticRoutes[$path])) {
            $route = $this->staticRoutes[$path];
            return $route->getHandler();
        }
        return null;
    }

    /**
     * Returning the best-matching prefix handler, or null if none match.
     *
     * @param $path string The request's path
     * @return HandlerInterface|null
     */
    private function getPrefixHandler($path)
    {
        // Find all prefixes that match the start of this path.
        $prefixes = array_keys($this->prefixRoutes);
        $matches = array_filter($prefixes, function ($prefix) use ($path) {
                return (strrpos($path, $prefix, -strlen($path)) !== false);
            });

        if ($matches) {
            // If there are multiple matches, sort them to find the one with the longest string length.
            if (count($matches) > 0) {
                usort($matches, function ($a, $b) {
                        return strlen($b) - strlen($a);
                    });
            }
            // Instantiate and return the handler identified as the best match.
            $route = $this->prefixRoutes[$matches[0]];
            return $route->getHandler();
        }
        return null;
    }

    private function getResponseFromRoutes(RequestInterface $request, array $args = null)
    {
        $response = null;

        $path = $request->getPath();

        // First check if there is a handler for this exact path.
        $handler = $this->getStaticHandler($path);
        if ($handler) {
            return $this->tryResponse($handler, $request, $args);
        }

        // Check prefix routes for any routes that match. Use the longest matching prefix.
        $handler = $this->getPrefixHandler($path);
        if ($handler) {
            return $this->tryResponse($handler, $request, $args);
        }

        // Try each of the routes.
        foreach ($this->routes as $route) {
            /** @var HandlerInterface $route */
            $response = $this->tryResponse($route, $request, $args);
            if ($response) {
                return $response;
            }
        }

        return null;
    }

    /**
     * Wraps the getResponse method in a try-catch.
     *
     * In an HttpException is caught while trying to get a response, the method returns a response based on the
     * HttpException's error code and message.
     *
     * @param HandlerInterface $handler The Route or Handler to try.
     * @param RequestInterface $request The incoming request.
     * @param array $args The array of arguments.
     * @return null|Response
     */
    private function tryResponse($handler, $request, $args)
    {
        $response = null;
        try {
            $response = $handler->getResponse($request, $args);
        } catch (HttpException $e) {
            $response = new Response();
            $response->setStatusCode($e->getCode());
            $response->setBody($e->getMessage());
        }
        return $response;
    }

    ////////////////
    // Deprecated //
    ////////////////

    /**
     * @deprecated Use {@see addRoute} instead.
     * @see addRoute
     */
    public function setPrefixRoute($prefixes, $handler)
    {
        $this->addPrefixRoute(new PrefixRoute($prefixes, $handler));
        trigger_error("Router::setPrefixRoute is deprecated. Use addRoute", E_USER_DEPRECATED);
    }

    /**
     * @deprecated Use {@see addRoute} instead.
     * @see addRoute
     */
    public function setStaticRoute($paths, $handler)
    {
        $this->addStaticRoute(new StaticRoute($paths, $handler));
        trigger_error("Router::setStaticRoute is deprecated. Use addRoute", E_USER_DEPRECATED);
    }
}
