<?php

namespace WellRESTed\Message;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Message defines core functionality for classes that represent HTTP messages.
 */
abstract class Message implements MessageInterface
{
    /** @var HeaderCollection */
    protected $headers;
    /** @var StreamInterface */
    protected $body;
    /** @var string */
    protected $protocolVersion = "1.1";

    /**
     * Create a new Message, optionally with headers and a body.
     *
     * If provided, $headers MUST by an associative array with header field
     * names as (string) keys and lists of header field values (string[])
     * as values.
     *
     * If no StreamInterface is provided for $body, the instance will create
     * a NullStream instance for the message body.
     *
     * @param array $headers Associative array of headers fields with header
     *     field names as keys and list arrays of field values as values
     * @param StreamInterface $body A stream representation of the message
     *     entity body
     */
    public function __construct(array $headers = null, StreamInterface $body = null)
    {
        $this->headers = new HeaderCollection();
        if ($headers) {
            foreach ($headers as $name => $values) {
                foreach ($values as $value) {
                    $this->headers[$name] = $value;
                }
            }
        }

        if ($body !== null) {
            $this->body = $body;
        } else {
            $this->body = new Stream('');
        }
    }

    public function __clone()
    {
        $this->headers = clone $this->headers;
    }

    // ------------------------------------------------------------------------
    // Psr\Http\Message\MessageInterface

    /**
     * Retrieves the HTTP protocol version as a string.
     *
     * The string MUST contain only the HTTP version number (e.g., "1.1", "1.0").
     *
     * @return string HTTP protocol version.
     */
    public function getProtocolVersion()
    {
        return $this->protocolVersion;
    }

    /**
     * Create a new instance with the specified HTTP protocol version.
     *
     * The version string MUST contain only the HTTP version number (e.g.,
     * "1.1", "1.0").
     *
     * @param string $version HTTP protocol version
     * @return static
     */
    public function withProtocolVersion($version)
    {
        $message = clone $this;
        $message->protocolVersion = $version;
        return $message;
    }

    /**
     * Retrieve all message headers.
     *
     * The keys represent the header name as it will be sent over the wire, and
     * each value is an array of strings associated with the header.
     *
     *     // Represent the headers as a string
     *     foreach ($message->getHeaders() as $name => $values) {
     *         echo $name . ": " . implode(", ", $values);
     *     }
     *
     *     // Emit headers iteratively:
     *     foreach ($message->getHeaders() as $name => $values) {
     *         foreach ($values as $value) {
     *             header(sprintf('%s: %s', $name, $value), false);
     *         }
     *     }
     *
     * While header names are not case-sensitive, getHeaders() will preserve the
     * exact case in which headers were originally specified.
     *
     * @return array Returns an associative array of the message's headers.
     */
    public function getHeaders()
    {
        $headers = [];
        foreach ($this->headers as $key => $value) {
            $headers[$key] = $value;
        }
        return $headers;
    }

    /**
     * Checks if a header exists by the given case-insensitive name.
     *
     * @param string $name Case-insensitive header field name.
     * @return bool Returns true if any header names match the given header
     *     name using a case-insensitive string comparison. Returns false if
     *     no matching header name is found in the message.
     */
    public function hasHeader($name)
    {
        return isset($this->headers[$name]);
    }

    /**
     * Retrieves a message header value by the given case-insensitive name.
     *
     * This method returns an array of all the header values of the given
     * case-insensitive header name.
     *
     * If the header does not appear in the message, this method returns an
     * empty array.
     *
     * @param string $name Case-insensitive header field name.
     * @return string[] An array of string values as provided for the given
     *    header. If the header does not appear in the message, this method
     *    returns an empty array.
     */
    public function getHeader($name)
    {
        if (isset($this->headers[$name])) {
            return $this->headers[$name];
        } else {
            return [];
        }
    }

    /**
     * Retrieves the line for a single header, with the header values as a
     * comma-separated string.
     *
     * This method returns all of the header values of the given
     * case-insensitive header name as a string concatenated together using
     * a comma.
     *
     * NOTE: Not all header values may be appropriately represented using
     * comma concatenation. For such headers, use getHeader() instead
     * and supply your own delimiter when concatenating.
     *
     * If the header does not appear in the message, this method returns an
     * empty string.
     *
     * @param string $name Case-insensitive header field name.
     * @return string A string of values as provided for the given header
     *    concatenated together using a comma. If the header does not appear in
     *    the message, this method returns an empty string.
     */
    public function getHeaderLine($name)
    {
        if (isset($this->headers[$name])) {
            return join(", ", $this->headers[$name]);
        } else {
            return "";
        }
    }

    /**
     * Create a new instance with the provided header, replacing any existing
     * values of any headers with the same case-insensitive name.
     *
     * While header names are case-insensitive, the casing of the header will
     * be preserved by this function, and returned from getHeaders().
     *
     * @param string $name Case-insensitive header field name.
     * @param string|string[] $value Header value(s).
     * @return static
     * @throws \InvalidArgumentException for invalid header names or values.
     */
    public function withHeader($name, $value)
    {
        $values = $this->getValidatedHeaders($name, $value);
        $message = clone $this;
        unset($message->headers[$name]);
        foreach ($values as $value) {
            $message->headers[$name] = (string) $value;
        }
        return $message;
    }

    /**
     * Creates a new instance, with the specified header appended with the
     * given value.
     *
     * Existing values for the specified header will be maintained. The new
     * value(s) will be appended to the existing list. If the header did not
     * exist previously, it will be added.
     *
     * @param string $name Case-insensitive header field name to add.
     * @param string|string[] $value Header value(s).
     * @return static
     * @throws \InvalidArgumentException for invalid header names or values.
     */
    public function withAddedHeader($name, $value)
    {
        $values = $this->getValidatedHeaders($name, $value);

        $message = clone $this;
        foreach ($values as $value) {
            $message->headers[$name] = (string) $value;
        }
        return $message;
    }

    /**
     * Creates a new instance, without the specified header.
     *
     * @param string $name Case-insensitive header field name to remove.
     * @return static
     */
    public function withoutHeader($name)
    {
        $message = clone $this;
        unset($message->headers[$name]);
        return $message;
    }

    /**
     * Gets the body of the message.
     *
     * @return StreamInterface Returns the body as a stream.
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Create a new instance, with the specified message body.
     *
     * The body MUST be a StreamInterface object.
     *
     * @param StreamInterface $body Body.
     * @return static
     * @throws \InvalidArgumentException When the body is not valid.
     */
    public function withBody(StreamInterface $body)
    {
        $message = clone $this;
        $message->body = $body;
        return $message;
    }

    // ------------------------------------------------------------------------

    private function getValidatedHeaders($name, $value)
    {
        $is_allowed = function ($item) {
            return is_string($item) || is_numeric($item);
        };

        if (!is_string($name)) {
            throw new \InvalidArgumentException("Header name must be a string");
        }

        if ($is_allowed($value)) {
            return [$value];
        } elseif (is_array($value) && count($value) === count(array_filter($value, $is_allowed))) {
            return $value;
        } else {
            throw new \InvalidArgumentException("Header values must be a string or string[]");
        }
    }
}
