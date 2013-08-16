<?php

/**
 * pjdietz\WellRESTed\RouteTarget
 *
 * @author PJ Dietz <pj@pjdietz.com>
 * @copyright Copyright 2013 by PJ Dietz
 * @license MIT
 */

namespace pjdietz\WellRESTed;

use pjdietz\WellRESTed\Interfaces\RequestInterface;
use pjdietz\WellRESTed\Interfaces\ResponseInterface;
use pjdietz\WellRESTed\Interfaces\RouterInterface;
use pjdietz\WellRESTed\Interfaces\RouteTargetInterface;

/**
 * RouteTarget
 *
 * RouteTarget defines the basic functionality for an instance dispatched when a Route pattern
 * is matched.
 */
abstract class RouteTarget implements RouteTargetInterface
{
    /** @var array  Matches array from the preg_match() call used to find this Handler */
    protected $args;
    /** @var RequestInterface  The HTTP request to respond to. */
    protected $request;
    /** @var ResponseInterface  The HTTP response to send based on the request. */
    protected $response;
    /** @var RouterInterface  The router that dispatched this handler */
    protected $router;

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
}
