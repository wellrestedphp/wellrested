<?php

/**
 * pjdietz\WellRESTed\Request
 *
 * @author PJ Dietz <pj@pjdietz.com>
 * @copyright Copyright 2013 by PJ Dietz
 * @license MIT
 */

namespace pjdietz\WellRESTed;

// TODO: Include port in the URI

/**
 * A Request instance represents an HTTP request. This class has two main uses:
 *
 * First, you can access a singleton instance via the getRequest() method that
 * represents the request sent to the server. The instance will contain the URI,
 * headers, body, etc.
 *
 * Second, you can create a custom Request and use it to obtain a Response
 * from a server through cURL.
 *
 * @property string hostname  Hostname part of the URI
 * @property string method  HTTP method (GET, POST, PUT, DELETE, etc.)
 * @property string path  Path component of the URI for the request
 * @property-read string pathParts  Fragments of the path, delimited by slashes
 * @property array query  Associative array of query parameters
 * @property array uri  Full URI (protocol, hostname, path, etc.)
 */
class Request extends Message
{
    /**
     * The Hostname for the request (e.g., www.google.com)
     *
     * @var string
     */
    protected $hostname;

    /**
     * HTTP method or verb for the request
     *
     * @var string
     */
    protected $method = 'GET';

    /**
     * Path component of the URI for the request
     *
     * @var string
     */
    protected $path = '/';

    /**
     * Array of fragments of the path, delimited by slashes
     *
     * @var array
     */
    protected $pathParts;

    /**
     * Associative array of query parameters
     *
     * @var array
     */
    protected $query;

    /**
     * Singleton instance derived from reading info from Apache.
     *
     * @var Request
     * @static
     */
    static protected $theRequest;

    // -------------------------------------------------------------------------
    // Accessors

    /**
     * Return the hostname portion of the URI
     *
     * @return string
     */
    public function getHostname()
    {
        return $this->hostname;
    }

    /**
     * Assign the hostname portion of the URI
     *
     * @param string $hostname
     */
    public function setHostname($hostname)
    {
        $this->hostname = $hostname;
    }

    /**
     * Return if the hostname portion of the URI is set.
     *
     * @return bool
     */
    public function issetHostName()
    {
        return isset($this->hostname);
    }

    /**
     * Unset the hostname portion of the URI.
     */
    public function unsetHostname()
    {
        unset($this->hostname);
    }

    /**
     * Return the method (e.g., GET, POST, PUT, DELETE)
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Assign the method (e.g., GET, POST, PUT, DELETE)
     *
     * @param string $method
     */
    public function setMethod($method)
    {
        $this->method = $method;
    }

    /**
     * Return the path part of the URI as a string.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set the path and pathParts members.
     *
     * @param string $path
     */
    public function setPath($path)
    {
        $this->path = $path;
        $this->pathParts = explode('/', substr($path, 1));
    }

    /**
     * Return an array of the sections of the path delimited by slashes.
     *
     * @return array
     */
    public function getPathParts()
    {
        return $this->pathParts;
    }

    /**
     * Return an associative array representing the query.
     *
     * @return array
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Set the query. The value passed can be a query string of key-value pairs
     * joined by ampersands or it can be an associative array.
     *
     * @param string|array $query
     * @throws \InvalidArgumentException
     */
    public function setQuery($query)
    {
        if (is_string($query)) {
            $qs = $query;
            parse_str($qs, $query);
        }

        if (is_array($query)) {
            $this->query = $query;
        } else {
            throw new \InvalidArgumentException('Unable to parse query string.');
        }
    }

    /**
     * Return the full URI includeing protocol, hostname, path, and query.
     *
     * @return array
     */
    public function getUri()
    {
        $uri = strtolower($this->protocol) . '://' . $this->hostname . $this->path;

        if ($this->query) {
            $uri .= '?' . http_build_query($this->query);
        }

        return $uri;
    }

    /**
     * Set the URI for the Request. This sets the other members, such as path,
     * hostname, etc.
     *
     * @param string $uri
     */
    public function setUri($uri)
    {
        $parsed = parse_url($uri);

        $host = isset($parsed['host']) ? $parsed['host'] : '';
        $this->setHostname($host);

        $path = isset($parsed['path']) ? $parsed['path'] : '';
        $this->setPath($path);

        $query = isset($parsed['query']) ? $parsed['query'] : '';
        $this->setQuery($query);
    }

    // -------------------------------------------------------------------------

    /**
     * Make a cURL request out of the instance and return a Response.
     *
     * @return Response
     * @throws exceptions\CurlException
     */
    public function request()
    {
        $ch = curl_init();

        // Set the URL.
        curl_setopt($ch, CURLOPT_URL, $this->uri);

        // Include headers in the response.
        curl_setopt($ch, CURLOPT_HEADER, 1);

        // Return the response from curl_exec().
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        // Set the method. Include the body, if needed.
        switch ($this->method) {

            case 'GET':
                curl_setopt($ch, CURLOPT_HTTPGET, 1);
                break;

            case 'POST':
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $this->body);
                break;

            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($ch, CURLOPT_POSTFIELDS, $this->body);
                break;

            default:
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $this->method);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $this->body);
                break;

        }

        // Add headers.
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headerLines);

        // Make the cURL request.
        $result = curl_exec($ch);

        // Throw an exception in the event of a cURL error.
        if ($result === false) {
            $error = curl_error($ch);
            $errno = curl_errno($ch);
            curl_close($ch);
            throw new Exceptions\CurlException($error, $errno);
        }

        // Make a reponse to populate and return with data obtained via cURL.
        $resp = new Response();

        $resp->statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // Split the result into headers and body.
        list ($headers, $body) = explode("\r\n\r\n", $result, 2);

        // Set the body. Do not auto-add the Content-length header.
        $resp->setBody($body, false);

        // Iterate over the headers line by line and add each one.
        foreach (explode("\r\n", $headers) as $header) {
            if (strpos($header, ':')) {
                list ($headerName, $headerValue) = explode(':', $header, 2);
                $resp->setHeader($headerName, ltrim($headerValue));
            }
        }

        curl_close($ch);

        return $resp;
    }

    /**
     * Set instance members based on the HTTP request sent to the server.
     */
    public function readHttpRequest()
    {
        $this->setBody(file_get_contents("php://input"), false);
        $this->headers = apache_request_headers();

        // Add case insensitive headers to the lookup table.
        foreach ($this->headers as $key => $value) {
            $this->headerLookup[strtolower($key)] = $key;
        }

        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->uri = $_SERVER['REQUEST_URI'];
        $this->hostname = $_SERVER['HTTP_HOST'];
    }

    /**
     * Return a reference to the singleton instance of the Request derived
     * from the server's information about the request sent to the script.
     *
     * @return Request
     * @static
     */
    public static function getRequest()
    {
        if (!isset(self::$theRequest)) {

            $klass = __CLASS__;
            $request = new $klass();
            $request->readHttpRequest();

            self::$theRequest = $request;

        }

        return self::$theRequest;
    }

}
