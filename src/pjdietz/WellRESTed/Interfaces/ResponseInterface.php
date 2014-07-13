<?php

/**
 * pjdietz\WellRESTed\Interfaces\ResponseInterface
 *
 * @author PJ Dietz <pj@pjdietz.com>
 * @copyright Copyright 2014 by PJ Dietz
 * @license MIT
 */

namespace pjdietz\WellRESTed\Interfaces;

/**
 * Interface for representing an HTTP response.
 */
interface ResponseInterface
{
    /** @return int  HTTP status code */
    public function getStatusCode();

    /** @param int $statusCode  HTTP status code */
    public function setStatusCode($statusCode);

    /**
     * Return the value for this header name
     *
     * @param $headerName
     * @return string $headerName
     */
    public function getHeader($headerName);

    /**
     * @param string $headerName
     * @param string $headerValue
     */
    public function setHeader($headerName, $headerValue);

    /** @return string */
    public function getBody();

    /** @param string $body */
    public function setBody($body);

    /** Issue the reponse to the client. */
    public function respond();
}
