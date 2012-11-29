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
     * The protocol. Set this to the protocol you wish to use, such as
     * "HTTP/1.1". If unset, the class uses $_SERVER['SERVER_PROTOCOL']
     *
     * @var string
     */
    public $protocol;

    /**
     * HTTP status code
     * @var int
     */
    public $statusCode;

    // -------------------------------------------------------------------------

    public function __construct($statusCode=500, $body=null, $headers=null) {

        $this->statusCode = $statusCode;

        if (is_array($headers)) {
            $this->headers = $headers;
        } else {
             $this->headers = array();
        }

        if (!is_null($body)) {
            $this->body = $body;
        }

    } // __construct()

    // -------------------------------------------------------------------------
    // !Accessors

    public function __get($name) {

        switch ($name) {
        case 'body':
            return $this->getBody();
        case 'headers':
            return $this->getHeaders();
        default:
            throw new \Exception('Property ' . $name . ' does not exist.');
        }

    } // __get()

    public function __set($name, $value) {

        switch ($name) {
        case 'body':
            $this->setBody($value);
            break;
        default:
            throw new \Exception('Property ' . $name . ' does not exist or is read only.');
        }

    } // __set()

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
     * @param bool $setContentLength  Automatically add a Content-length header
     */
    public function setBody($value, $setContentLength=true) {

        $this->body = $value;

        if ($setContentLength === true) {
            $this->setHeader('Content-Length', strlen($value));
        }

    } // setBody()

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
     * @return bool
     */
    public function hasHeader($header) {
        return isset($this->headers[$header]);
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
     * Output the response to the client.
     *
     * @param bool $headersOnly  Do not include the body, only the headers.
     */
    public function respond($headersOnly=false) {

        // Output the HTTP status code.
        header($this->getStatusLine($this->statusCode));

        // Output each header.
        foreach ($this->headers as $header => $value) {
            header($header . ': ' . $value);
        }

        // Output the entity body.
        if (!$headersOnly && isset($this->body)) {
            print $this->body;
        }

    } // respond()

    /**
     * Return HTTP status line, e.g. HTTP/1.1 200 OK.
     *
     * @return string
     * @throws \UnexpectedValueException
     */
     protected function getStatusLine() {

        switch ($this->statusCode) {
            case 100: $text = 'Continue'; break;
            case 101: $text = 'Switching Protocols'; break;
            case 200: $text = 'OK'; break;
            case 201: $text = 'Created'; break;
            case 202: $text = 'Accepted'; break;
            case 203: $text = 'Non-Authoritative Information'; break;
            case 204: $text = 'No Content'; break;
            case 205: $text = 'Reset Content'; break;
            case 206: $text = 'Partial Content'; break;
            case 300: $text = 'Multiple Choices'; break;
            case 301: $text = 'Moved Permanently'; break;
            case 302: $text = 'Moved Temporarily'; break;
            case 303: $text = 'See Other'; break;
            case 304: $text = 'Not Modified'; break;
            case 305: $text = 'Use Proxy'; break;
            case 400: $text = 'Bad Request'; break;
            case 401: $text = 'Unauthorized'; break;
            case 402: $text = 'Payment Required'; break;
            case 403: $text = 'Forbidden'; break;
            case 404: $text = 'Not Found'; break;
            case 405: $text = 'Method Not Allowed'; break;
            case 406: $text = 'Not Acceptable'; break;
            case 407: $text = 'Proxy Authentication Required'; break;
            case 408: $text = 'Request Time-out'; break;
            case 409: $text = 'Conflict'; break;
            case 410: $text = 'Gone'; break;
            case 411: $text = 'Length Required'; break;
            case 412: $text = 'Precondition Failed'; break;
            case 413: $text = 'Request Entity Too Large'; break;
            case 414: $text = 'Request-URI Too Large'; break;
            case 415: $text = 'Unsupported Media Type'; break;
            case 500: $text = 'Internal Server Error'; break;
            case 501: $text = 'Not Implemented'; break;
            case 502: $text = 'Bad Gateway'; break;
            case 503: $text = 'Service Unavailable'; break;
            case 504: $text = 'Gateway Time-out'; break;
            case 505: $text = 'HTTP Version not supported'; break;
            default:
                throw new \UnexpectedValueException('Unknown http status code "' . $this->statusCode . '"');
                break;
        }

        if (isset($this->protocol)) {
            $protocol = $this->protocol;
        } elseif (isset($_SERVER['SERVER_PROTOCOL'])) {
            $protocol = $_SERVER['SERVER_PROTOCOL'];
        } else {
            $protocol = 'HTTP/1.1';
        }

        return $protocol . ' ' . $this->statusCode . ' ' . $text;

    }

} // Response

?>
