<?php

namespace wellrested;

/*******************************************************************************
 * Response
 *
 * A Response instance allows you to build an HTTP response and send it when
 * finished.
 *
 * @package WellRESTed
 *
 ******************************************************************************/

/**
 * @property string body  Entity body of the response
 * @property array headers  Associative array of headers
 */
class Response {

    /**
     * Entity body of the response
     * @var string
     */
    protected $body;

    /**
     * Associative array of headers
     * @var array
     */
    protected $headers;

    /**
     * HTTP status code
     * @var int
     */
    public $statusCode;

    // -------------------------------------------------------------------------

    public function __construct($statusCode=500, $body='', $headers=null) {

        $this->statusCode = $statusCode;
        $this->body = $body;

        if (is_array($headers)) {
            $this->headers = $headers;
        } else {
             $this->headers = array();
        }

    }

    // -------------------------------------------------------------------------
    // !Accessors

    public function __get($name) {

        switch ($name) {
        case 'body':
            return $this->getBody();
        case 'headers':
            return $this->getHeaders();
        default:
            throw new Exception('Property ' . $name . ' does not exist.');
        }

    } // __get()

    public function __set($name, $value) {

        switch ($name) {
        case 'body':
            return $this->setBody($value);
        default:
            throw new Exception('Property ' . $name . ' does not exist or is read only.');
        }

    } // __get()

    public function getBody() {
        return $this->body;
    }

    public function getHeaders() {
        return $this->headers;
    }

    /**
     * Provide a new entity body for the respone.
     * This method also updates the content-length header based on the length
     * of the new body string.
     *
     * @param string $value
     */
    public function setBody($value) {
        $this->body = $value;
        $this->setHeader('Content-Length', strlen($value));
    }


    /**
     * Add or update a header to a given value
     *
     * @param string $header
     * @param string $value
     */
    public function setHeader($header, $value) {
        $this->headers[$header] = $value;
    }

    /**
     * Return if the response contains a header with the given key.
     *
     * @param string $header
     * @param bool
     */
    public function hasHeader($header) {
        return isset($this->headers[$header]);

    }

    /**
     * Return the value of a given header, or false if it does not exist.
     *
     * @param string $header
     * @return string|false
     */
    public function getHeader($header) {

        if ($this->hasHeader($header)) {
            return $this->headers[$header];
        }

        return false;

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

    // -------------------------------------------------------------------------

    /**
     * Output the response to the client. This function also terminates the
     * script to prevent and additional output from contaminating the response.
     *
     * @param bool $headersOnly  Do not include the body, only the headers.
     */
    public function respond($headersOnly = false) {

        // Output the HTTP status code.
        http_response_code($this->statusCode);

        // Output each header.
        foreach ($this->headers as $header => $value) {
            header($header . ': ' . $value);
        }

        // Output the entity body.
        if (!$headersOnly && isset($this->body)) {
            print $this->body;
        }

        exit;

    }

}

?>
