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

/**
 * A Handler issues a response for a given resource.
 *
 * @property-read ResponseInterface response The Response to the request
 */
abstract class Handler extends RouteTarget implements HandlerInterface
{
    /**
     * @param RequestInterface $request
     * @return ResponseInterface
     */
    public function getResponse(RequestInterface $request = null)
    {
        if (!is_null($request)) {
            $this->request = $request;
        }
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
