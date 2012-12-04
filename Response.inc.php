<?php

namespace wellrested;

require_once(dirname(__FILE__) . '/Message.inc.php');

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
 * @property string reasonPhrase  Text explanation of status code.
 * @property int statusCode  HTTP status code
 * @property string statusLine  HTTP status line, e.g. "HTTP/1.1 200 OK"
 */
class Response extends Message {

    /**
     * Text explanation of the HTTP Status Code. You only need to set this if
     * you are using nonstandard status codes. Otherwise, the instance will
     * set the when you update the status code.
     *
     * @var string
     */
    protected $reasonPhrase;

    /**
     * HTTP status code
     * @var int
     */
    protected $statusCode;

    // -------------------------------------------------------------------------

    /**
     * Create a new Response instance, optionally passing a status code, body,
     * and headers.
     *
     * @param int $statusCode
     * @param string $body
     * @param array $headers
     */
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

        if (isset($_SERVER['SERVER_PROTOCOL'])) {
            $this->protocol = $_SERVER['SERVER_PROTOCOL'];
        } else {
            $this->protocol = 'HTTP/1.1';
        }

    } // __construct()

    // -------------------------------------------------------------------------
    // !Accessors

    /**
     * @param string $name
     * @return array|string
     * @throws \Exception
     */
    public function __get($name) {

        switch ($name) {
            case 'reasonPhrase':
                return $this->getReasonPhrase();
            case 'statusCode':
                return $this->getStatusCode();
            case 'statusLine':
                return $this->getStatusLine();
            default:
                return parent::__get($name);
        }

    } // __get()

    /**
     * @param string $name
     * @param mixed $value
     * @throws \Exception
     */
    public function __set($name, $value) {

        switch ($name) {
            case 'reasonPhrase':
                $this->setReasonPhrase($value);
                return;
            case 'statusCode':
                $this->setStatusCode($value);
                return;
            default:
                parent::__set($name, $value);
        }

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

    }

    /**
     * @return string
     */
    public function getReasonPhrase() {
        return $this->reasonPhrase;
    }

    /**
     * @param string $statusCodeMessage
     */
    public function setReasonPhrase($statusCodeMessage) {
        $this->reasonPhrase = $statusCodeMessage;
    }

    /**
     * @return int
     */
    public function getStatusCode() {
        return $this->statusCode;
    } // setBody()

    /**
     * @param int $statusCode
     * @param string $reasonPhrase
     * @throws \InvalidArgumentException
     * @return void
     */
    public function setStatusCode($statusCode, $reasonPhrase=null) {

        $this->statusCode = (int) $statusCode;

        if (is_null($reasonPhrase)) {

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
                default:  $text = 'Nonstandard'; break;
            }

            $this->reasonPhrase = $text;

        } else {

            if (is_string($reasonPhrase)) {
                $this->reasonPhrase = $reasonPhrase;
            } else {
                throw new \InvalidArgumentException('$reasonPhrase must be a string (or null to use standard HTTP Reason-Phrase');
            }

        }

    }

    /**
     * Return HTTP status line, e.g. HTTP/1.1 200 OK.
     *
     * @return string
     */
    protected function getStatusLine() {
        return sprintf('%s/%s %s %s',
                    strtoupper($this->protocol),
                    $this->protocolVersion,
                    $this->statusCode,
                    $this->reasonPhrase);
    }

    // -------------------------------------------------------------------------

    /**
     * Output the response to the client.
     *
     * @param bool $headersOnly  Do not include the body, only the headers.
     */
    public function respond($headersOnly=false) {

        // Output the HTTP status code.
        header($this->statusLine);

        // Output each header.
        foreach ($this->headers as $header => $value) {
            header($header . ': ' . $value);
        }

        // Output the entity body.
        if (!$headersOnly && isset($this->body)) {
            print $this->body;
        }

    } // respond()

} // Response

?>
