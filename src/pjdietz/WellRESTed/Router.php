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
use pjdietz\WellRESTed\Routes\PrefixRoute;
use pjdietz\WellRESTed\Routes\StaticRoute;

/**
 * Router
 *
 * A Router uses a table of Routes to find the appropriate Handler for a request.
 */
class Router implements HandlerInterface
{
    /** @var array  Hash array HTTP verb => RouteTable */
    private $routeTables;

    /** @var array  Hash array of status code => error handler */
    private $errorHandlers;

    /** Create a new Router. */
    public function __construct()
    {
        $this->routeTables = array();
        $this->errorHandlers = array();
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
     * Return the response built by the handler based on the request
     *
     * @param RequestInterface $request
     * @param array|null $args
     * @return ResponseInterface
     */
    public function getResponse(RequestInterface $request, array $args = null)
    {
        $response = $this->getResponseFromRouteTables($request, $args);
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

    private function getResponseFromRouteTables(RequestInterface $request, array $args = null)
    {
        $method = $request->getMethod();
        if (isset($this->routeTables[$method])) {
            $table = $this->routeTables[$method];
            return $this->tryResponse($table, $request, $args);
        }

        if (isset($this->routeTables["*"])) {
            $table = $this->routeTables["*"];
            return $this->tryResponse($table, $request, $args);
        }

        return null;
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

    /**
     * @deprecated Use {@see addRoute} instead.
     * @see addRoute
     */
    public function setPrefixRoute($prefixes, $handler)
    {
        $this->addRoute(new PrefixRoute($prefixes, $handler));
        trigger_error("Router::setPrefixRoute is deprecated. Use addRoute", E_USER_DEPRECATED);
    }

    /**
     * Append a new route to the route table.
     *
     * @param HandlerInterface $route
     * @param string $method HTTP Method; * for any
     */
    public function addRoute(HandlerInterface $route, $method = "*")
    {
        $table = $this->getRouteTable($method);
        $table->addRoute($route);
    }

    ////////////////
    // Deprecated //
    ////////////////

    private function getRouteTable($method = "*")
    {
        if (!isset($this->routeTables[$method])) {
            $this->routeTables[$method] = new RouteTable();
        }
        return $this->routeTables[$method];
    }

    /**
     * @deprecated Use {@see addRoute} instead.
     * @see addRoute
     */
    public function setStaticRoute($paths, $handler)
    {
        $this->addRoute(new StaticRoute($paths, $handler));
        trigger_error("Router::setStaticRoute is deprecated. Use addRoute", E_USER_DEPRECATED);
    }
}
