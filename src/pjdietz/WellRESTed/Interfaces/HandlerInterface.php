<?php

/**
 * pjdietz\WellRESTed\Interfaces\HandlerInterface
 *
 * @author PJ Dietz <pj@pjdietz.com>
 * @copyright Copyright 2014 by PJ Dietz
 * @license MIT
 */

namespace pjdietz\WellRESTed\Interfaces;

/**
 * Provides a mechanism for obtaining a response given a request.
 * @package pjdietz\WellRESTed
 */
interface HandlerInterface {

    /**
     * @param RequestInterface $request The request to build a response for.
     * @param array|null $args Optional map of arguments.
     * @return ResponseInterface|null A response, or null if this handler will not handle.
     */
    public function getResponse(RequestInterface $request, array $args = null);

}
