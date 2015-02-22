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
use pjdietz\WellRESTed\Routes\RouteFactory;
use pjdietz\WellRESTed\Routes\StaticRoute;

/**
 * Router
 *
 * A Router uses a table of Routes to find the appropriate Handler for a request.
 */
class Router implements HandlerInterface
{
    /** @var array  Hash array of status code => error handler */
    private $errorHandlers;
    /** @var RouteTable */
    private $routeTable;

    /** Create a new Router. */
    public function __construct()
    {
        $this->errorHandlers = array();
        $this->routeTable = new RouteTable();
    }

    /**
     * Add a route or series of routes to the Router.
     *
     * When adding a single route, the first argument should be the path, path prefix, URI template, or regex pattern.
     * The method will attempt to find the best type of route based on this argument and send the remainding arguments
     * to that routes constructor. @see {RouteFactory::createRoute}
     *
     * To add multiple routes, pass arrays to add where each array contains an argument list.
     */
    public function add()
    {
        $factory = new RouteFactory();

        $args = func_get_args();
        if (count($args) > 1 && is_array($args[0])) {
            foreach ($args as $argumentList) {
                $route = call_user_func_array(array($factory, "createRoute"), $argumentList);
                $this->addRoute($route);
            }
            return;
        }

        $route = call_user_func_array(array($factory, "createRoute"), $args);
        $this->addRoute($route);
    }

    /**
     * Append a series of routes.
     *
     * @param array $routes List array of routes
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
     * Append a new route to the route table.
     *
     * @param HandlerInterface $route
     */
    public function addRoute(HandlerInterface $route)
    {
        $this->routeTable->addRoute($route);
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
     * @param mixed $errorHandler
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
        $response = $this->tryResponse($this->routeTable, $request, $args);
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

    private function getErrorResponse($status, $request, $args = null, $response = null)
    {
        if (isset($this->errorHandlers[$status])) {
            // Pass the response triggering this along to the error handler.
            $errorArgs = array("response" => $response);
            if ($args) {
                $errorArgs = array_merge($args, $errorArgs);
            }
            $unpacker = new HandlerUnpacker();
            $handler = $unpacker->unpack($this->errorHandlers[$status], $request, $errorArgs);
            if (!is_null($handler) && $handler instanceof HandlerInterface) {
                return $handler->getResponse($request, $errorArgs);
            }
            return $handler;
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
        $this->addRoute(new PrefixRoute($prefixes, $handler));
        trigger_error("Router::setPrefixRoute is deprecated. Use addRoute", E_USER_DEPRECATED);
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
