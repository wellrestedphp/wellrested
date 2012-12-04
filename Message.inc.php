<?php

namespace wellrested;

/**
 * Common base class for the Request and Response classes.
 *
 * @property string body       Entity body of the message
 * @property array headers     Associative array of HTTP headers
 */
abstract class Message {

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
     * Name of the protocol to use.
     * @var string
     */
    protected $protocol = 'HTTP';

    /**
     * Version of the protocol to use.
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
    public function __get($name) {

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
    public function __set($name, $value) {

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
    public function getBody() {
        return $this->body;
    }

    /**
     * Set the body for the request.
     *
     * @param string $body
     */
    public function setBody($body) {
        $this->body = $body;
    }

    /**
     * Return an associative array of all set headers.
     *
     * @return array
     */
    public function getHeaders() {
        return $this->headers;
    }

    /**
     * Return the value of a given header, or false if it does not exist.
     *
     * @param string $header
     * @return string|bool
     */
    public function getHeader($header) {

        if ($this->hasHeader($header)) {
            return $this->headers[$header];
        }

        return false;

    }

    /**
     * Add or update a header to a given value
     *
     * @param string $header
     * @param $value
     * @param string $value
     */
    public function setHeader($header, $value) {
        $this->headers[$header] = $value;
    }

    /**
     * Return if the response contains a header with the given key.
     *
     * @param $header
     * @return bool
     */
    public function hasHeader($header) {
        return isset($this->headers[$header]);
    }

    /**
     * Remove a header. This method does nothing if the header does not exist.
     *
     * @param string $header
     */
    public function unsetHeader($header) {
        if ($this->hasHeader($header)) {
            unset($this->headers[$header]);
        }
    }

    /**
     * @return string
     */
    public function getProtocol() {
        return $this->protocol;
    }

    /**
     * @param $protocol
     */
    public function setProtocol($protocol) {

        if (strpos($protocol, '/') === false) {
            list($this->protocol, $this->protocolVersion) = explode('/', $protocol, 2);
        } else {
            $this->protocol = $protocol;
        }

    }

    /**
     * @return string
     */
    public function getProtocolVersion() {
        return $this->protocolVersion;
    }

    /**
     * @param string $protocolVersion
     */
    public function setProtocolVersion($protocolVersion) {
        $this->protocolVersion = $protocolVersion;
    }

}

?>
