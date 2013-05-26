<?php

/**
 * pjdietz\WellRESTed\Interfaces\RouterInterface
 *
 * @author PJ Dietz <pj@pjdietz.com>
 * @copyright Copyright 2013 by PJ Dietz
 * @license MIT
 */

namespace pjdietz\WellRESTed\Interfaces;

/**
 * The RouterInterface provides a mechanism for obtaining a response given a request.
 * @package pjdietz\WellRESTed
 */
interface RouterInterface
{
    /**
     * Return the response for the given request.
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     */
    public function getResponse(RequestInterface $request = null);
}