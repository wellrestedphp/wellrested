<?php

namespace pjdietz\WellRESTed;

/**
 * Common base class for the Request and Response classes.
 *
 * @property string body       Entity body of the message
 * @property array headers     Associative array of HTTP headers
 */
abstract class Message
{
    /**
     * Entity body of the message
     *
     * @var string
     */
    protected $body;

    /**
     * Associative array of HTTP headers
     *
     * @var array
     */
    protected $headers;

    /**
     * Associative array of lowercase header field names as keys with
     * corresponding case sensitive field names from the $headers array as
     * values.
     *
     * @var array
     */
    protected $headerLookup;

    /**
     * Name of the protocol to use.
     *
     * @var string
     */
    protected $protocol = 'HTTP';

    /**
     * Version of the protocol to use.
     *
     * @var string
     */
    protected $protocolVersion = '1.1';

    // -------------------------------------------------------------------------
    // !Accessors

    /**
     * @param string $name
     * @return array|string
     * @throws \Exception
     */
    public function __get($name)
    {
        switch ($name) {
            case 'body':
                return $this->getBody();
            case 'headers':
                return $this->getHeaders();
            case 'protocol':
                return $this->getProtocol();
            case 'protocolVersion':
                return $this->getProtocolVersion();
            default:
                throw new \Exception('Property ' . $name . ' does not exist.');
        }
    }

    /**
     * @param string $name
     * @param $value
     * @throws \Exception
     */
    public function __set($name, $value)
    {
        switch ($name) {
            case 'body':
                $this->setBody($value);
                return;
            case 'protocol':
                $this->setProtocol($value);
                return;
            case 'protocolVersion':
                $this->setProtocolVersion($value);
                return;
            default:
                throw new \Exception('Property ' . $name . 'does not exist or is read-only.');
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
    public function hasHeader($name)
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
     * @return string
     */
    public function getProtocol()
    {
        return $this->protocol;
    }

    /**
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
     * @return string
     */
    public function getProtocolVersion()
    {
        return $this->protocolVersion;
    }

    /**
     * @param string $protocolVersion
     */
    public function setProtocolVersion($protocolVersion)
    {
        $this->protocolVersion = $protocolVersion;
    }

}
