<?php

/**
 * pjdietz\WellRESTed\Handler
 *
 * @author PJ Dietz <pj@pjdietz.com>
 * @copyright Copyright 2013 by PJ Dietz
 * @license MIT
 */

namespace pjdietz\WellRESTed;

use pjdietz\WellRESTed\Interfaces\HandlerInterface;
use pjdietz\WellRESTed\Interfaces\RequestInterface;
use pjdietz\WellRESTed\Interfaces\ResponseInterface;
use pjdietz\WellRESTed\Interfaces\RouterInterface;

/**
 * A Handler issues a response for a given resource.
 *
 * @property-read ResponseInterface response The Response to the request
 */
abstract class Handler implements HandlerInterface
{
    /** @var array  Matches array from the preg_match() call used to find this Handler */
    protected $args;
    /** @var RequestInterface  The HTTP request to respond to. */
    protected $request;
    /** @var ResponseInterface  The HTTP response to send based on the request. */
    protected $response;
    /** @var RouterInterface  The router that dispatched this handler */
    protected $router;

    // -------------------------------------------------------------------------
    // Accessors

    /**
     * Magic function for properties
     *
     * @param string $propertyName
     * @return mixed
     */
    public function __get($propertyName)
    {
        $method = 'get' . ucfirst($propertyName);
        if (method_exists($this, $method)) {
            return $this->{$method}();
        }
        return null;
    }

    /** @param array $args */
    public function setArguments(array $args)
    {
        $this->args = $args;
    }

    /** @return array */
    public function getArguments()
    {
        return $this->args;
    }

    /** @return RequestInterface */
    public function getRequest()
    {
        return $this->request;
    }

    /** @param RequestInterface $request */
    public function setRequest(RequestInterface $request)
    {
        $this->request = $request;
    }

    /** @return RouterInterface */
    public function getRouter()
    {
        return $this->router;
    }

    /** @param RouterInterface $router */
    public function setRouter(RouterInterface $router)
    {
        $this->router = $router;
    }

    /** @return ResponseInterface */
    public function getResponse()
    {
        $this->response = new Response();
        $this->buildResponse();
        return $this->response;
    }

    /**
     * Prepare the Response. Override this method if your subclass needs to
     * repond to any non-standard HTTP methods. Otherwise, override the
     * get, post, put, etc. methods.
     */
    protected function buildResponse()
    {
        switch ($this->request->getMethod()) {
            case 'GET':
                $this->get();
                break;
            case 'HEAD':
                $this->head();
                break;
            case 'POST':
                $this->post();
                break;
            case 'PUT':
                $this->put();
                break;
            case 'DELETE':
                $this->delete();
                break;
            case 'PATCH':
                $this->patch();
                break;
            case 'OPTIONS':
                $this->options();
                break;
            default:
                $this->respondWithMethodNotAllowed();
        }
    }

    // -------------------------------------------------------------------------
    // HTTP Methods

    // Each of these methods corresponds to a standard HTTP method. Each method
    // has no arguments and returns nothing, but should affect the instance's
    // response member.
    //
    // By default, the methods will provide a 405 Method Not Allowed header.

    /**
     * Method for handling HTTP GET requests.
     *
     * This method should modify the instance's response member.
     */
    protected function get()
    {
        $this->respondWithMethodNotAllowed();
    }

    /**
     * Method for handling HTTP HEAD requests.
     *
     * This method should modify the instance's response member.
     */
    protected function head()
    {
        // The default function calls the instance's get() method, then sets
        // the resonse's body member to an empty string.

        $this->get();

        if ($this->response->getStatusCode() == 200) {
            $this->response->setBody('', false);
        }
    }

    /**
     * Method for handling HTTP POST requests.
     *
     * This method should modify the instance's response member.
     */
    protected function post()
    {
        $this->respondWithMethodNotAllowed();
    }

    /**
     * Method for handling HTTP PUT requests.
     *
     * This method should modify the instance's response member.
     */
    protected function put()
    {
        $this->respondWithMethodNotAllowed();
    }

    /**
     * Method for handling HTTP DELETE requests.
     *
     * This method should modify the instance's response member.
     */
    protected function delete()
    {
        $this->respondWithMethodNotAllowed();
    }

    /**
     * Method for handling HTTP PATCH requests.
     *
     * This method should modify the instance's response member.
     */
    protected function patch()
    {
        $this->respondWithMethodNotAllowed();
    }

    /**
     * Method for handling HTTP OPTION requests.
     *
     * This method should modify the instance's response member.
     */
    protected function options()
    {
        if ($this->addAllowHeader()) {
            $this->response->setStatusCode(200);
        } else {
            $this->response->setStatusCode(405);
        }
    }

    /**
     * Provide a default response for unsupported methods.
     */
    protected function respondWithMethodNotAllowed()
    {
        $this->response->setStatusCode(405);
        $this->addAllowHeader();
    }

    /** @return array of method names supported by the handler. */
    protected function getAllowedMethods()
    {
    }

    /**
     * Add an Allow: header using the methods returned by getAllowedMethods()
     *
     * @return bool  The header was added.
     */
    protected function addAllowHeader()
    {
        $methods = $this->getAllowedMethods();
        if ($methods) {
            $this->response->setHeader('Allow', join($methods, ', '));
            return true;
        }
        return false;
    }

}
