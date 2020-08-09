<?php

namespace WellRESTed\Message;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Representation of an outgoing, server-side response.
 *
 * Per the HTTP specification, this interface includes properties for
 * each of the following:
 *
 * - Protocol version
 * - Status code and reason phrase
 * - Headers
 * - Message body
 */
class Response extends Message implements ResponseInterface
{
    /** @var string Text explanation of the HTTP Status Code. */
    private $reasonPhrase;
    /** @var int HTTP status code */
    private $statusCode;

    /**
     * Create a new Response, optionally with status code, headers, and a body.
     *
     * If provided, $headers MUST by an associative array with header field
     * names as (string) keys and lists of header field values (string[])
     * as values.
     *
     * If no StreamInterface is provided for $body, the instance will create
     * a NullStream instance for the message body.
     *
     * @see \WellRESTed\Message\Message
     * @param int $statusCode
     * @param array $headers
     * @param StreamInterface $body
     */
    public function __construct($statusCode = 500, array $headers = null, StreamInterface $body = null)
    {
        parent::__construct($headers, $body);
        $this->statusCode = $statusCode;
        $this->reasonPhrase = $this->getDefaultReasonPhraseForStatusCode($statusCode);
    }

    // ------------------------------------------------------------------------
    // Psr\Http\Message\ResponseInterface

    /**
     * Gets the response status code.
     *
     * The status code is a 3-digit integer result code of the server's attempt
     * to understand and satisfy the request.
     *
     * @return integer Status code.
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Create a new instance with the specified status code, and optionally
     * reason phrase, for the response.
     *
     * If no reason phrase is specified, this method will provide a standard
     * reason phrase, if possible.
     *
     * @link http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     * @param int $code The 3-digit integer result code to set.
     * @param string $reasonPhrase The reason phrase to use with the
     *     provided status code; if none is provided, implementations MAY
     *     use the defaults as suggested in the HTTP specification.
     * @return static
     * @throws \InvalidArgumentException For invalid status code arguments.
     */
    public function withStatus($code, $reasonPhrase = "")
    {
        $response = clone $this;
        $response->statusCode = $code;
        if (!$reasonPhrase) {
            $reasonPhrase = $this->getDefaultReasonPhraseForStatusCode($code);
        }
        $response->reasonPhrase = $reasonPhrase;
        return $response;
    }

    /**
     * Gets the response reason phrase, a short textual description of the status code.
     *
     * The reason phrase is not required and may be an empty string.
     *
     * @link http://tools.ietf.org/html/rfc7231#section-6
     * @link http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     * @return string Reason phrase, or an empty string if unknown.
     */
    public function getReasonPhrase()
    {
        return $this->reasonPhrase;
    }

    /**
     * @param int $statusCode
     * @return string
     */
    private function getDefaultReasonPhraseForStatusCode($statusCode)
    {
        $reasonPhraseLookup = [
            100 => "Continue",
            101 => "Switching Protocols",
            102 => "Processing",
            200 => "OK",
            201 => "Created",
            202 => "Accepted",
            203 => "Non-Authoritative Information",
            204 => "No Content",
            205 => "Reset Content",
            206 => "Partial Content",
            207 => "Multi-Status",
            208 => "Already Reported",
            226 => "IM Used",
            300 => "Multiple Choices",
            301 => "Moved Permanently",
            302 => "Found",
            303 => "See Other",
            304 => "Not Modified",
            305 => "Use Proxy",
            307 => "Temporary Redirect",
            308 => "Permanent Redirect",
            400 => "Bad Request",
            401 => "Unauthorized",
            402 => "Payment Required",
            403 => "Forbidden",
            404 => "Not Found",
            405 => "Method Not Allowed",
            406 => "Not Acceptable",
            407 => "Proxy Authentication Required",
            408 => "Request Timeout",
            409 => "Conflict",
            410 => "Gone",
            411 => "Length Required",
            412 => "Precondition Failed",
            413 => "Payload Too Large",
            414 => "URI Too Long",
            415 => "Unsupported Media Type",
            416 => "Range Not Satisfiable",
            417 => "Expectation Failed",
            421 => "Misdirected Request",
            422 => "Unprocessable Entity",
            423 => "Locked",
            424 => "Failed Dependency",
            426 => "Upgrade Required",
            428 => "Precondition Required",
            429 => "Too Many Requests",
            431 => "Request Header Fields Too Large",
            500 => "Internal Server Error",
            501 => "Not Implemented",
            502 => "Bad Gateway",
            503 => "Service Unavailable",
            504 => "Gateway Timeout",
            505 => "HTTP Version Not Supported",
            506 => "Variant Also Negotiates",
            507 => "Insufficient Storage",
            508 => "Loop Detected",
            510 => "Not Extended",
            511 => "Network Authentication Required"
        ];
        return $reasonPhraseLookup[$statusCode] ?? '';
    }
}
