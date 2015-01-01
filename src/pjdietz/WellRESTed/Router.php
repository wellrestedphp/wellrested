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
    /** @var array  Hash array of status code => qualified HandlerInterface names for error handling. */
    private $errorHandlers;

    /** Create a new Router. */
    public function __construct()
    {
        $this->routes = array();
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
        $path = $request->getPath();
        if (array_key_exists($path, $this->routes)) {
            $handler = new $this->routes[$path]();
            $response = $handler->getResponse($request, $args);
        } else {
            foreach ($this->routes as $path => $route) {
                // Only take elements that are not $path => $handler mapped.
                if (is_int($path)) {
                    /** @var HandlerInterface $route */
                    try {
                        $response = $route->getResponse($request, $args);
                    } catch (HttpException $e) {
                        $response = new Response();
                        $response->setStatusCode($e->getCode());
                        $response->setBody($e->getMessage());
                    }
                    if ($response) break;
                }
            }
        }
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
            return $response;
        }
        return null;
    }

    /**
     * Append a new route to the route route table.
     *
     * @param HandlerInterface $route
     */
    public function addRoute(HandlerInterface $route)
    {
        if ($route instanceof StaticRoute) {
            $handler = $route->getHandler();
            foreach ($route->getPaths() as $path) {
                $this->routes[$path] = $handler;
            }
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

}
