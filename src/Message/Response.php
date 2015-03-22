<?php

namespace WellRESTed\Message;

use Psr\Http\Message\ResponseInterface;

class Response extends Message implements ResponseInterface
{
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
    /** @var string HTTP protocol and version */

    // Psr\Http\Message\ResponseInterface ------------------------------------------------------------------------------

    /**
     * Gets the response Status-Code.
     *
     * The Status-Code is a 3-digit integer result code of the server's attempt
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
     * If no Reason-Phrase is specified, implementations MAY choose to default
     * to the RFC 7231 or IANA recommended reason phrase for the response's
     * Status-Code.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * updated status and reason phrase.
     *
     * @link http://tools.ietf.org/html/rfc7231#section-6
     * @link http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     * @param integer $code The 3-digit integer result code to set.
     * @param null|string $reasonPhrase The reason phrase to use with the
     *     provided status code; if none is provided, implementations MAY
     *     use the defaults as suggested in the HTTP specification.
     * @return self
     * @throws \InvalidArgumentException For invalid status code arguments.
     */
    public function withStatus($code, $reasonPhrase = null)
    {
        $response = clone $this;
        $response->statusCode = $code;
        if ($reasonPhrase === null) {
            static $reasonPhraseLookup = null;
            if ($reasonPhraseLookup === null) {
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
            }
            if (isset($reasonPhraseLookup[$code])) {
                $reasonPhrase = $reasonPhraseLookup[$code];
            } else {
                $reasonPhrase = "Unknown";
            }
        }
        $response->reasonPhrase = $reasonPhrase;
        return $response;
    }

    /**
     * Gets the response Reason-Phrase, a short textual description of the Status-Code.
     *
     * Because a Reason-Phrase is not a required element in a response
     * Status-Line, the Reason-Phrase value MAY be null. Implementations MAY
     * choose to return the default RFC 7231 recommended reason phrase (or those
     * listed in the IANA HTTP Status Code Registry) for the response's
     * Status-Code.
     *
     * @link http://tools.ietf.org/html/rfc7231#section-6
     * @link http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     * @return string|null Reason phrase, or null if unknown.
     */
    public function getReasonPhrase()
    {
        return $this->reasonPhrase;
    }
}
