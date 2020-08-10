<?php

namespace WellRESTed\Message;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * Representation of an outgoing, client-side request.
 *
 * Per the HTTP specification, this interface includes properties for
 * each of the following:
 *
 * - Protocol version
 * - HTTP method
 * - URI
 * - Headers
 * - Message body
 */
class Request extends Message implements RequestInterface
{
    /** @var string  */
    protected $method;
    /** @var string|null */
    protected $requestTarget;
    /** @var UriInterface */
    protected $uri;

    // ------------------------------------------------------------------------

    /**
     * Create a new Request.
     *
     * @see \WellRESTed\Message\Message
     * @param string|UriInterface $uri
     * @param string $method
     * @param array $headers
     * @param StreamInterface|null $body
     */
    public function __construct(
        string $method = 'GET',
        $uri = '',
        array $headers = [],
        ?StreamInterface $body = null
    ) {
        parent::__construct($headers, $body);
        $this->method = $method;
        if (!($uri instanceof UriInterface)) {
            $uri = new Uri($uri);
        }
        $this->uri = $uri;
        $this->requestTarget = null;
    }

    public function __clone()
    {
        $this->uri = clone $this->uri;
        parent::__clone();
    }

    // ------------------------------------------------------------------------
    // Psr\Http\Message\RequestInterface

    /**
     * Retrieves the message's request target.
     *
     * Retrieves the message's request-target either as it will appear (for
     * clients), as it appeared at request (for servers), or as it was
     * specified for the instance (see withRequestTarget()).
     *
     * In most cases, this will be the origin-form of the composed URI,
     * unless a value was provided to the concrete implementation (see
     * withRequestTarget() below).
     *
     * If no URI is available, and no request-target has been specifically
     * provided, this method will return the string "/".
     *
     * @return string
     */
    public function getRequestTarget()
    {
        // Use the explicitly set request target first.
        if ($this->requestTarget !== null) {
            return $this->requestTarget;
        }

        // Build the origin form from the composed URI.
        $target = $this->uri->getPath();
        $query = $this->uri->getQuery();
        if ($query) {
            $target .= '?' . $query;
        }

        // Return "/" if the origin form is empty.
        return $target ?: '/';
    }

    /**
     * Create a new instance with a specific request-target.
     *
     * If the request needs a non-origin-form request-target — e.g., for
     * specifying an absolute-form, authority-form, or asterisk-form —
     * this method may be used to create an instance with the specified
     * request-target, verbatim.
     *
     * @link http://tools.ietf.org/html/rfc7230#section-2.7 (for the various
     *     request-target forms allowed in request messages)
     * @param mixed $requestTarget
     * @return static
     */
    public function withRequestTarget($requestTarget)
    {
        $request = clone $this;
        $request->requestTarget = $requestTarget;
        return $request;
    }

    /**
     * Retrieves the HTTP method of the request.
     *
     * @return string Returns the request method.
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Create a new instance with the provided HTTP method.
     *
     * While HTTP method names are typically all uppercase characters, HTTP
     * method names are case-sensitive. Therefore, this method will not
     * modify the given string.
     *
     * @param string $method Case-insensitive method.
     * @return static
     * @throws \InvalidArgumentException for invalid HTTP methods.
     */
    public function withMethod($method)
    {
        $request = clone $this;
        $request->method = $this->getValidatedMethod($method);
        return $request;
    }

    /**
     * Retrieves the URI instance.
     *
     * @link http://tools.ietf.org/html/rfc3986#section-4.3
     * @return UriInterface Returns a UriInterface instance
     *     representing the URI of the request.
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * Returns an instance with the provided URI.
     *
     * This method updates the Host header of the returned request by
     * default if the URI contains a host component. If the URI does not
     * contain a host component, any pre-existing Host header will be carried
     * over to the returned request.
     *
     * You can opt-in to preserving the original state of the Host header by
     * setting `$preserveHost` to `true`. When `$preserveHost` is set to
     * `true`, this method interacts with the Host header in the following ways:
     *
     * - If the the Host header is missing or empty, and the new URI contains
     *   a host component, this method will update the Host header in the returned
     *   request.
     * - If the Host header is missing or empty, and the new URI does not contain a
     *   host component, this method will not update the Host header in the returned
     *   request.
     * - If a Host header is present and non-empty, this method will not update
     *   the Host header in the returned request.
     *
     * @link http://tools.ietf.org/html/rfc3986#section-4.3
     * @param UriInterface $uri New request URI to use.
     * @param bool $preserveHost Preserve the original state of the Host header.
     * @return static
     */
    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        $request = clone $this;

        $newHost = $uri->getHost();
        $oldHost = isset($request->headers['Host']) ? $request->headers['Host'] : '';

        if ($preserveHost === false) {
            // Update Host
            if ($newHost && $newHost !== $oldHost) {
                unset($request->headers['Host']);
                $request->headers['Host'] = $newHost;
            }
        } else {
            // Preserve Host
            if (!$oldHost && $newHost) {
                $request->headers['Host'] = $newHost;
            }
        }

        $request->uri = $uri;
        return $request;
    }

    // ------------------------------------------------------------------------

    /**
     * @param mixed $method
     * @return string
     * @throws \InvalidArgumentException
     */
    private function getValidatedMethod($method)
    {
        if (!is_string($method)) {
            throw new \InvalidArgumentException('Method must be a string.');
        }
        $method = trim($method);
        if (strpos($method, ' ') !== false) {
            throw new \InvalidArgumentException('Method cannot contain spaces.');
        }
        return $method;
    }
}
