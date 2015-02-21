<?php

/**
 * pjdietz\WellRESTed\Interfaces\ResponseInterface
 *
 * @author PJ Dietz <pj@pjdietz.com>
 * @copyright Copyright 2015 by PJ Dietz
 * @license MIT
 */

namespace pjdietz\WellRESTed\Interfaces;

/**
 * Interface for representing an HTTP response.
 */
interface ResponseInterface
{
    /**
     * Return the HTTP status code
     *
     * @return int HTTP status code
     */
    public function getStatusCode();

    /**
     * Set the status code for the response.
     *
     * @param int $statusCode HTTP status code
     */
    public function setStatusCode($statusCode);

    /**
     * Return the value for a given header.
     *
     * Per RFC 2616, HTTP headers are case-insensitive. Take care to conform to
     * this when implementing.
     *
     * @param string $headerName Field name of the header
     * @return string Header field value
     */
    public function getHeader($headerName);

    /**
     * Set the value for a given header.
     *
     * Per RFC 2616, HTTP headers are case-insensitive. Take care to conform to
     * this when implementing.
     *
     * @param string $headerName Field name
     * @param string $headerValue Field value
     */
    public function setHeader($headerName, $headerValue);

    /**
     * Return the body of the response.
     *
     * @return string Response body
     */
    public function getBody();

    /**
     * Set the body of the response.
     *
     * @param string $body Response body
     */
    public function setBody($body);

    /** Issue the reponse to the client. */
    public function respond();
}
