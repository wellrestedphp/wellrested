<?php

/**
 * pjdietz\WellRESTed\Interfaces\HandlerInterface
 *
 * @author PJ Dietz <pj@pjdietz.com>
 * @copyright Copyright 2015 by PJ Dietz
 * @license MIT
 */

namespace pjdietz\WellRESTed\Interfaces;

/**
 * Provides a mechanism for obtaining a response given a request.
 */
interface HandlerInterface
{
    /**
     * Return the handled response.
     *
     * @param RequestInterface $request The request to respond to.
     * @param array|null $args Optional additional arguments.
     * @return ResponseInterface The handled response.
     */
    public function getResponse(RequestInterface $request, array $args = null);
}
