<?php

/**
 * pjdietz\WellRESTed\Message
 *
 * @author PJ Dietz <pj@pjdietz.com>
 * @copyright Copyright 2015 by PJ Dietz
 * @license MIT
 */

namespace pjdietz\WellRESTed;

/**
 * Common base class for the Request and Response classes.
 */
abstract class Message
{
    /** @var string  Entity body of the message */
    protected $body;
    /** @var array Associative array of HTTP headers */
    protected $headers;
    /**
     * Associative array of lowercase header field names as keys with
     * corresponding case sensitive field names from the $headers array as
     * values.
     *
     * @var array
     */
    protected $headerLookup;

    // -------------------------------------------------------------------------

    /**
     * Create a new HTTP message.
     */
    public function __construct()
    {
        $this->headers = array();
        $this->headerLookup = array();
    }

    // -------------------------------------------------------------------------
    // Accessors

    /**
     * Return the body payload of the instance.
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Set the body for the request.
     *
     * @param string $body
     */
    public function setBody($body)
    {
        $this->body = $body;
    }

    /**
     * Return an associative array of all set headers.
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Return the value of a given header, or false if it does not exist.
     *
     * @param string $name
     * @return string|null
     */
    public function getHeader($name)
    {
        $lowerName = strtolower($name);
        if (isset($this->headerLookup[$lowerName])) {
            $realName = $this->headerLookup[$lowerName];
            return $this->headers[$realName];
        }
        return null;
    }

    /**
     * Add or update a header to a given value
     *
     * @param string $name
     * @param $value
     * @param string $value
     */
    public function setHeader($name, $value)
    {
        $lowerName = strtolower($name);

        // Check if a mapping already exists for this header.
        // Remove it, if needed.
        if (isset($this->headerLookup[$lowerName])
            && $this->headerLookup[$lowerName] !== $name
        ) {
            unset($this->headers[$this->headerLookup[$lowerName]]);
        }

        // Store the actual header.
        $this->headers[$name] = $value;

        // Store a mapping to the user's prefered case.
        $this->headerLookup[$lowerName] = $name;
    }

    /**
     * Return if the response contains a header with the given key.
     *
     * @param $name
     * @return bool
     */
    public function issetHeader($name)
    {
        $lowerName = strtolower($name);
        return isset($this->headerLookup[$lowerName]);
    }

    /**
     * Remove a header. This method does nothing if the header does not exist.
     *
     * @param string $name
     */
    public function unsetHeader($name)
    {
        $lowerName = strtolower($name);
        if (isset($this->headerLookup[$lowerName])) {
            $realName = $this->headerLookup[$lowerName];
            if (isset($this->headers[$realName])) {
                unset($this->headers[$realName]);
            }
            unset($this->headerLookup[$lowerName]);
        }
    }
}
