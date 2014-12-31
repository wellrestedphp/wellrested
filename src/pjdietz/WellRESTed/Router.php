<?php

/**
 * pjdietz\WellRESTed\Router
 *
 * @author PJ Dietz <pj@pjdietz.com>
 * @copyright Copyright 2014 by PJ Dietz
 * @license MIT
 */

namespace pjdietz\WellRESTed;

use pjdietz\WellRESTed\Interfaces\HandlerInterface;
use pjdietz\WellRESTed\Interfaces\RequestInterface;
use pjdietz\WellRESTed\Interfaces\ResponseInterface;

/**
 * Router
 *
 * A Router uses a table of Routes to find the appropriate Handler for a request.
 */
class Router implements HandlerInterface
{
    /** @var array  Array of Route objects */
    private $routes;
    /** @var array  Array of Route objects for error handling. */
    private $errorHandlers;

    /** Create a new Router. */
    public function __construct()
    {
        $this->routes = array();
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
        foreach ($this->routes as $route) {
            /** @var HandlerInterface $route */
            $responce = $route->getResponse($request, $args);
            if ($responce) {
                return $responce;
            }
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
        $this->routes[] = $route;
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
     * @param integer $error The error code.
     * @param HandlerInterface $errorHandler The handler for the error.
     */
    public function addErrorHandler($error, $errorHandler)
    {
        $this->errorHandlers[$error] = $errorHandler;
    }

    /**
     * Add custom error handlers.
     *
     * @param array $errorHandlers An array mapping an integer error code to something implementing an HandlerInterface.
     */
    public function addErrorHandlers(array $errorHandlers)
    {
        foreach ($errorHandlers as $error => $errorHandler) {
            $this->addErrorHandler($error, $errorHandler);
        }
    }

    /**
     * Dispatch the singleton Request through the router and output the response.
     *
     * Respond with a 404 Not Found if no route provides a response.
     */
    public function respond($args = null)
    {
        $request = Request::getRequest();
        $response = $this->getResponse($request, $args);
        if (!$response) {
            $response = $this->getNoRouteResponse($request);
        }
        $status = $response->getStatusCode();
        if (array_key_exists($status, $this->errorHandlers)) {
            $errorHandler = new $this->errorHandlers[$status]();
            $response = $errorHandler->getResponse($request, $args);
        }
        $response->respond();
    }

    /**
     * Prepare a resonse indicating a 404 Not Found error
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
