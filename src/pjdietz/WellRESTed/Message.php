<?php

/**
 * pjdietz\WellRESTed\Message
 *
 * @author PJ Dietz <pj@pjdietz.com>
 * @copyright Copyright 2013 by PJ Dietz
 * @license MIT
 */

namespace pjdietz\WellRESTed;

/**
 * Common base class for the Request and Response classes.
 *
 * @property string body  Entity body of the message
 * @property-read array headers  Associative array of HTTP headers
 * @property-read array headerLines  Numeric array of HTTP headers
 * @property string protocol  The protocol, e.g. HTTP
 * @property string protocolVersion  The version of the protocol
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
    /** @var string Name of the protocol to use. */
    protected $protocol = 'HTTP';
    /** @var string Version of the protocol to use. */
    protected $protocolVersion = '1.1';

    // -------------------------------------------------------------------------

    public function __construct()
    {
        $this->headers = array();
        $this->headerLookup = array();
    }

    // -------------------------------------------------------------------------
    // Accessors

    /**
     * Magic accessor method
     *
     * @param string $propertyName
     * @return mixed
     */
    public function __get($propertyName)
    {
        $method = 'get' . ucfirst($propertyName);
        if (method_exists($this, $method)) {
            return $this->{$method}();
        }
        return null;
    }

    /**
     * Magic accessor method
     *
     * @param string $propertyName
     * @param $value
     */
    public function __set($propertyName, $value)
    {
        $method = 'set' . ucfirst($propertyName);
        if (method_exists($this, $method)) {
            $this->{$method}($value);
        }
    }

    /**
     * Magic accessor method
     *
     * @param string $propertyName
     * @return boolean
     */
    public function __isset($propertyName)
    {
        $method = 'isset' . ucfirst($propertyName);
        if (method_exists($this, $method)) {
            return $this->{$method}();
        }
        return false;
    }

    /**
     * Magic accessor method
     *
     * @param string $propertyName
     */
    public function __unset($propertyName)
    {
        $method = 'unset' . ucfirst($propertyName);
        if (method_exists($this, $method)) {
            $this->{$method}();
        }
    }

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
     * Return if the body is set
     *
     * @return bool
     */
    public function issetBody()
    {
        return isset($this->body);
    }

    /** Unset the body property */
    public function unsetBody()
    {
        unset($this->body);
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
     * Return an array containing one string for each header as "field: value"
     *
     * @return string
     */
    public function getHeaderLines()
    {
        $lines = array();
        foreach ($this->headers as $field => $value) {
            $lines[] = sprintf('%s: %s', $field, $value);
        }
        return $lines;
    }

    /**
     * Return the value of a given header, or false if it does not exist.
     *
     * @param string $name
     * @return string|bool
     */
    public function getHeader($name)
    {
        $lowerName = strtolower($name);

        if (isset($this->headerLookup[$lowerName])) {

            $realName = $this->headerLookup[$lowerName];

            if (isset($this->headers[$realName])) {
                return $this->headers[$realName];
            }

        }

        return false;
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

    /**
     * Return the protocol (e.g., HTTP)
     *
     * @return string
     */
    public function getProtocol()
    {
        return $this->protocol;
    }

    /**
     * Set the protocol for the message.
     *
     * The value is expected to be the name of the protocol only. If the
     * version is included, the version is striped and set as the
     * protocolVersion property.
     *
     * <code>
     * $instance->protocol = 'HTTP1/1';
     * print $instance->protocol; // 'HTTP';
     * print $instance->protocolVersion; // '1.1';
     * </code>
     *
     * @param $protocol
     */
    public function setProtocol($protocol)
    {
        if (strpos($protocol, '/') === false) {
            list($this->protocol, $this->protocolVersion) = explode('/', $protocol, 2);
        } else {
            $this->protocol = $protocol;
        }
    }

    /**
     * Return if the protocol property is set.
     *
     * @return bool
     */
    public function issetProtocol()
    {
        return isset($this->protocol);
    }

    /** Unset the protocol property. */
    public function unsetProtocol()
    {
        unset($this->protocol);
    }

    /**
     * Return the version portion of the protocol. For HTTP/1.1, this is 1.1
     *
     * @return string
     */
    public function getProtocolVersion()
    {
        return $this->protocolVersion;
    }

    /**
     * Assign a new protocol version
     *
     * @param string $protocolVersion
     */
    public function setProtocolVersion($protocolVersion)
    {
        $this->protocolVersion = $protocolVersion;
    }

    /**
     * Return if the version portion of the protocol is set.
     *
     * @return bool
     */
    public function issetProtocolVersion()
    {
        return isset($this->protocolVersion);
    }

    /** Unset the version portion of the protocol. */
    public function unsetProtocolVersion()
    {
        unset($this->protocolVersion);
    }
}
