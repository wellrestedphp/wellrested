<?php

/**
 * pjdietz\WellRESTed\Response
 *
 * @author PJ Dietz <pj@pjdietz.com>
 * @copyright Copyright 2014 by PJ Dietz
 * @license MIT
 */

namespace pjdietz\WellRESTed;

use InvalidArgumentException;
use pjdietz\WellRESTed\Interfaces\ResponseInterface;

/**
 * A Response instance allows you to build an HTTP response and send it when
 * finished.
 */
class Response extends Message implements ResponseInterface
{
    const CHUNK_SIZE = 1048576;

    /** @var string Path to a file to read and output as the body. */
    private $bodyFilePath;
    /**
     * Text explanation of the HTTP Status Code. You only need to set this if
     * you are using nonstandard status codes. Otherwise, the instance will
     * set the when you update the status code.
     *
     * @var string
     */
    private $reasonPhrase;
    /** @var int  HTTP status code */
    private $statusCode;
    /** @var string HTTP protocol and version*/
    private $protocol = "HTTP/1.1";

    // -------------------------------------------------------------------------

    /**
     * Create a new Response instance, optionally passing a status code, body,
     * and headers.
     *
     * @param int $statusCode
     * @param string $body
     * @param array $headers
     */
    public function __construct($statusCode = 500, $body = null, $headers = null)
    {
        parent::__construct();

        $this->setStatusCode($statusCode);

        if (is_array($headers)) {
            $this->headers = $headers;
        }

        if (!is_null($body)) {
            $this->body = $body;
        }
    }

    // -------------------------------------------------------------------------
    // Accessors

    /**
     * Provide a new entity body for the respone.
     * This method also updates the content-length header based on the length
     * of the new body string.
     *
     * @param string $value
     * @param bool $setContentLength  Automatically add a Content-length header
     */
    public function setBody($value, $setContentLength = true)
    {
        $this->body = $value;
        if ($setContentLength === true) {
            $this->setHeader('Content-Length', strlen($value));
        }
    }

    /**
     * Provide the path to a file to output as the response body.
     *
     * @param string $bodyFilePath Filepath
     */
    public function setBodyFilePath($bodyFilePath)
    {
        $this->bodyFilePath = $bodyFilePath;
    }

    /**
     * Return the path to the file to output as the response body.
     *
     * @return string Filepath
     */
    public function getBodyFilePath()
    {
        return $this->bodyFilePath;
    }

    /**
     * Return the portion of the status line explaining the status.
     *
     * @return string
     */
    public function getReasonPhrase()
    {
        return $this->reasonPhrase;
    }

    /**
     * Return true for status codes in the 1xx-3xx range.
     *
     * @return bool
     */
    public function getSuccess()
    {
        return $this->statusCode < 400;
    }

    /**
     * Return the HTTP status code for the response.
     *
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Return the HTTP status line, e.g. HTTP/1.1 200 OK.
     *
     * @return string
     */
    public function getStatusLine()
    {
        return $this->protocol . " " . $this->statusCode . " " . $this->reasonPhrase;
    }

    /**
     * Set the status code and optionally the reason phrase explaining it.
     *
     * @param int $statusCode
     * @param string|null $reasonPhrase
     * @throws InvalidArgumentException
     */
    public function setStatusCode($statusCode, $reasonPhrase = null)
    {
        $this->statusCode = (int) $statusCode;

        if (is_null($reasonPhrase)) {

            switch ($this->statusCode) {
                case 100:
                    $text = 'Continue';
                    break;
                case 101:
                    $text = 'Switching Protocols';
                    break;
                case 200:
                    $text = 'OK';
                    break;
                case 201:
                    $text = 'Created';
                    break;
                case 202:
                    $text = 'Accepted';
                    break;
                case 203:
                    $text = 'Non-Authoritative Information';
                    break;
                case 204:
                    $text = 'No Content';
                    break;
                case 205:
                    $text = 'Reset Content';
                    break;
                case 206:
                    $text = 'Partial Content';
                    break;
                case 300:
                    $text = 'Multiple Choices';
                    break;
                case 301:
                    $text = 'Moved Permanently';
                    break;
                case 302:
                    $text = 'Found';
                    break;
                case 303:
                    $text = 'See Other';
                    break;
                case 304:
                    $text = 'Not Modified';
                    break;
                case 305:
                    $text = 'Use Proxy';
                    break;
                case 400:
                    $text = 'Bad Request';
                    break;
                case 401:
                    $text = 'Unauthorized';
                    break;
                case 402:
                    $text = 'Payment Required';
                    break;
                case 403:
                    $text = 'Forbidden';
                    break;
                case 404:
                    $text = 'Not Found';
                    break;
                case 405:
                    $text = 'Method Not Allowed';
                    break;
                case 406:
                    $text = 'Not Acceptable';
                    break;
                case 407:
                    $text = 'Proxy Authentication Required';
                    break;
                case 408:
                    $text = 'Request Timeout';
                    break;
                case 409:
                    $text = 'Conflict';
                    break;
                case 410:
                    $text = 'Gone';
                    break;
                case 411:
                    $text = 'Length Required';
                    break;
                case 412:
                    $text = 'Precondition Failed';
                    break;
                case 413:
                    $text = 'Request Entity Too Large';
                    break;
                case 414:
                    $text = 'Request-URI Too Long';
                    break;
                case 415:
                    $text = 'Unsupported Media Type';
                    break;
                case 500:
                    $text = 'Internal Server Error';
                    break;
                case 501:
                    $text = 'Not Implemented';
                    break;
                case 502:
                    $text = 'Bad Gateway';
                    break;
                case 503:
                    $text = 'Service Unavailable';
                    break;
                case 504:
                    $text = 'Gateway Timeout';
                    break;
                case 505:
                    $text = 'HTTP Version Not Supported';
                    break;
                default:
                    $text = 'Nonstandard';
                    break;
            }

            $this->reasonPhrase = $text;

        } else {

            if (is_string($reasonPhrase)) {
                $this->reasonPhrase = $reasonPhrase;
            } else {
                throw new InvalidArgumentException('$reasonPhrase must be a string (or null to use standard HTTP Reason-Phrase');
            }

        }

    }

    /**
     * Output the response to the client.
     *
     * @param bool $headersOnly  Do not include the body, only the headers.
     */
    public function respond($headersOnly = false)
    {
        // Output the HTTP status code.
        header($this->getStatusLine());

        // Output each header.
        foreach ($this->headers as $header => $value) {
            header($header . ': ' . $value);
        }

        // Output the entity body.
        if (!$headersOnly) {
            if (isset($this->bodyFilePath) && $this->bodyFilePath && file_exists($this->bodyFilePath)) {
                $this->outputBodyFile();
            } else {
                print $this->body;
            }
        }
    }

    // -------------------------------------------------------------------------

    /** Output the contents of a file */
    private function outputBodyFile()
    {
        $handle = fopen($this->getBodyFilePath(), 'rb');
        if ($handle !== false) {
            while (!feof($handle)) {
                $buffer = fread($handle, self::CHUNK_SIZE);
                print $buffer;
                flush();
            }
        }
    }
}
