<?php

/**
 * pjdietz\WellRESTed\Interfaces\RequestInterface
 *
 * @author PJ Dietz <pj@pjdietz.com>
 * @copyright Copyright 2014 by PJ Dietz
 * @license MIT
 */

namespace pjdietz\WellRESTed\Interfaces;

/**
 * Interface for representing an HTTP request.
 * @package pjdietz\WellRESTed
 */
interface RequestInterface
{
    /** @return string  HTTP request method (e.g., GET, POST, PUT). */
    public function getMethod();

    /** @return string  Path component of the request URI */
    public function getPath();

    /** @return array  Query paramters as key-value pairs */
    public function getQuery();

    /**
     * Return the value for this header name
     *
     * @param $headerName
     * @return string $headerName
     */
    public function getHeader($headerName);

    /** @return string  Requst body */
    public function getBody();

}
