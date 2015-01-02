<?php

/**
 * pjdietz\WellRESTed\Router
 *
 * @author PJ Dietz <pj@pjdietz.com>
 * @copyright Copyright 2014 by PJ Dietz
 * @license MIT
 */

namespace pjdietz\WellRESTed;

use pjdietz\WellRESTed\Exceptions\HttpExceptions\HttpException;
use pjdietz\WellRESTed\Interfaces\HandlerInterface;
use pjdietz\WellRESTed\Interfaces\RequestInterface;
use pjdietz\WellRESTed\Interfaces\ResponseInterface;
use pjdietz\WellRESTed\Interfaces\Routes\StaticRouteInterface;

/**
 * Router
 *
 * A Router uses a table of Routes to find the appropriate Handler for a request.
 */
class Router implements HandlerInterface
{
    /** @var array  Array of Route objects */
    private $routes;

    /** @var array  Hash array mapping exact paths to handlers */
    private $staticRoutes;

    /** @var array  Hash array of status code => qualified HandlerInterface names for error handling. */
    private $errorHandlers;

    /** Create a new Router. */
    public function __construct()
    {
        $this->routes = array();
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
            if (array_key_exists($status, $this->errorHandlers)) {
                /** @var HandlerInterface $errorHandler */
                $errorHandler = new $this->errorHandlers[$status]();
                // Pass the response triggering this along to the error handler.
                $errorArgs = array("response" => $response);
                if ($args) {
                    $errorArgs = array_merge($args, $errorArgs);
                }
                return $errorHandler->getResponse($request, $errorArgs);
            }
        }
        return $response;
    }

    /**
     * Append a new route to the route route table.
     *
     * @param HandlerInterface $route
     */
    public function addRoute(HandlerInterface $route)
    {
        if ($route instanceof StaticRouteInterface) {
            $this->setStaticRoute($route->getPaths(), $route->getHandler());
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
     * A route for an exact match to a path.
     *
     * @param string|array $paths Path component of the URI or a list of paths
     * @param string $handler Fully qualified name to an autoloadable handler class.
     */
    public function setStaticRoute($paths, $handler)
    {
        if (is_string($paths)) {
            $paths = array($paths);
        }
        foreach ($paths as $path) {
            $this->staticRoutes[$path] = $handler;
        }
    }

    /**
     * Add a custom error handler.
     *
     * @param integer $statusCode The error status code.
     * @param string $errorHandler Fully qualified name to an autoloadable handler class.
     */
    public function setErrorHandler($statusCode, $errorHandler)
    {
        $this->errorHandlers[$statusCode] = $errorHandler;
    }

    /**
     * Add custom error handlers.
     *
     * @param array $errorHandlers Array mapping integer error codes to qualified handler names.
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
        $response = new Response(404);
        $response->setBody('No resource at ' . $request->getPath());
        return $response;
    }

    private function getStaticHandler($path)
    {
        if (array_key_exists($path, $this->staticRoutes)) {
            return new $this->staticRoutes[$path]();
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
            $reponse = $this->tryResponse($handler, $request, $args);
            if ($reponse) {
                return $reponse;
            }
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
}
