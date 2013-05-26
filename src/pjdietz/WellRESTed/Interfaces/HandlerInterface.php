<?php

/**
 * pjdietz\WellRESTed\Interfaces\HandlerInterface
 *
 * @author PJ Dietz <pj@pjdietz.com>
 * @copyright Copyright 2013 by PJ Dietz
 * @license MIT
 */

namespace pjdietz\WellRESTed\Interfaces;

/**
 * Interface for a creating a response in reaction to a request or arguments.
 * @package pjdietz\WellRESTed
 */
interface HandlerInterface
{
    /** @return array  Associative array used to obtain a response */
    public function getArguments();

    /** @param array $args  Associative array used to obtain a response */
    public function setArguments(array $args);

    /** @return RequestInterface  Request used to obtain a response */
    public function getRequest();

    /** @param RequestInterface $request  Request used to obtain a response */
    public function setRequest(RequestInterface $request);

    /** @return RouterInterface  Reference to the router used to dispatch this handler */
    public function getRouter();

    /** @param RouterInterface $router  Request used to obtain a response */
    public function setRouter(RouterInterface $router);

    /** @return ResponseInterface  Response obtained based on the args and request */
    public function getResponse();
}
