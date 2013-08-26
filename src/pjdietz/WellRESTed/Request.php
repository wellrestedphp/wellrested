<?php

/**
 * pjdietz\WellRESTed\Request
 *
 * @author PJ Dietz <pj@pjdietz.com>
 * @copyright Copyright 2013 by PJ Dietz
 * @license MIT
 */

namespace pjdietz\WellRESTed;

use pjdietz\WellRESTed\Exceptions\CurlException;
use pjdietz\WellRESTed\Interfaces\RequestInterface;
use pjdietz\WellRESTed\Interfaces\RoutableInterface;

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
class Request extends Message implements RoutableInterface
{
    /**
     * Singleton instance derived from reading info from Apache.
     *
     * @var Request
     * @static
     */
    static protected $theRequest;
    /** @var string  The Hostname for the request (e.g., www.google.com) */
    private $hostname;
    /** @var string  HTTP method or verb for the request */
    private $method = 'GET';
    /** @var string   Path component of the URI for the request */
    private $path = '/';
    /** @var array Array of fragments of the path, delimited by slashes */
    private $pathParts;
    /** @var int */
    private $port = 80;
    /**@var array Associative array of query parameters */
    private $query;
    /** @var int internal count of the number of times routers have dispatched this instance */
    private $routeDepth = 0;

    // -------------------------------------------------------------------------

    /**
     * Create a new Request instance.
     *
     * @param string|null $uri
     * @param string $method
     */
    public function __construct($uri = null, $method = 'GET')
    {
        parent::__construct();

        if (!is_null($uri)) {
            $this->setUri($uri);
        }

        $this->method = $method;
    }

    // -------------------------------------------------------------------------

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
            $request = new Request();
            $request->readHttpRequest();
            self::$theRequest = $request;
        }

        return self::$theRequest;
    }

    /** @return array all request headers from the current request. */
    public static function getRequestHeaders()
    {
        if (function_exists('apache_request_headers')) {
            return apache_request_headers();
        }

        // If apache_request_headers is not available, use this, based on replacement code from
        // the PHP manual.

        $arh = array();
        $rx_http = '/\AHTTP_/';
        foreach ($_SERVER as $key => $val) {
            if (preg_match($rx_http, $key)) {
                $arh_key = preg_replace($rx_http, '', $key);
                // do some nasty string manipulations to restore the original letter case
                // this should work in most cases
                $rx_matches = explode('_', $arh_key);
                if (count($rx_matches) > 0 and strlen($arh_key) > 2) {
                    foreach ($rx_matches as $ak_key => $ak_val) {
                        $rx_matches[$ak_key] = ucfirst(
                            $ak_val
                        );
                    }
                    $arh_key = implode('-', $rx_matches);
                }
                $arh[$arh_key] = $val;
            }
        }
        return $arh;
    }

    /**
     * Set instance members based on the HTTP request sent to the server.
     */
    public function readHttpRequest()
    {
        $this->setBody(file_get_contents("php://input"), false);
        $this->headers = self::getRequestHeaders();

        // Add case insensitive headers to the lookup table.
        foreach ($this->headers as $key => $value) {
            $this->headerLookup[strtolower($key)] = $key;
        }

        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->uri = $_SERVER['REQUEST_URI'];
        $this->hostname = $_SERVER['HTTP_HOST'];
    }

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

    /** @return int */
    public function getPort()
    {
        return $this->port;
    }

    /** @param int $port */
    public function setPort($port)
    {
        $this->port = $port;
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
        $uri = strtolower($this->protocol) . '://' . $this->hostname;

        if ($this->port !== 80) {
            $uri .= ':' . $this->port;
        }

        $uri .= $this->path;

        if ($this->query) {
            $uri .= '?' . http_build_query($this->query);
        }

        return $uri;
    }

    /**
     * Set the URI for the Request. This sets the other members: hostname,
     * path, port, and query.
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

        $port = isset($parsed['port']) ? (int) $parsed['port'] : 80;
        $this->setPort($port);

        $query = isset($parsed['query']) ? $parsed['query'] : '';
        $this->setQuery($query);
    }

    // -------------------------------------------------------------------------

    /** @return int The number of times a router has dispatched this Routable */
    public function getRouteDepth()
    {
        return $this->routeDepth;
    }

    /** Increase the instance's internal count of its depth in nested route tables */
    public function incrementRouteDepth()
    {
        $this->routeDepth++;
    }

    // -------------------------------------------------------------------------

    /**
     * Make a cURL request out of the instance and return a Response.
     *
     * @param array|null $curlOpts  Associative array of options to set using curl_setopt_array before making the request.
     * @throws Exceptions\CurlException
     * @return Response
     */
    public function request($curlOpts = null)
    {
        $ch = curl_init();

        $options = array(
            CURLOPT_URL => $this->getUri(),
            CURLOPT_PORT => $this->port,
            CURLOPT_HEADER => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HTTPHEADER => $this->getHeaderLines()
        );

        // Set the method. Include the body, if needed.
        switch ($this->method) {
            case 'GET':
                $options[CURLOPT_HTTPGET] = 1;
                break;
            case 'POST':
                $options[CURLOPT_POST] = 1;
                $options[CURLOPT_POSTFIELDS] = $this->body;
                break;
            case 'PUT':
                $options[CURLOPT_CUSTOMREQUEST] = 'PUT';
                $options[CURLOPT_POSTFIELDS] = $this->body;
                break;
            default:
                $options[CURLOPT_CUSTOMREQUEST] = $this->method;
                $options[CURLOPT_POSTFIELDS] = $this->body;
                break;
        }

        // Override cURL options with the user options passed in.
        if ($curlOpts) {
            foreach ($curlOpts as $optKey => $optValue) {
                $options[$optKey] = $optValue;
            }
        }

        // Set the cURL options.
        curl_setopt_array($ch, $options);

        // Make the cURL request.
        $result = curl_exec($ch);

        // Throw an exception in the event of a cURL error.
        if ($result === false) {
            $error = curl_error($ch);
            $errno = curl_errno($ch);
            curl_close($ch);
            throw new CurlException($error, $errno);
        }

        // Make a reponse to populate and return with data obtained via cURL.
        $resp = new Response();

        $resp->statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // Split the result into headers and body.
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers = substr($result, 0, $headerSize);
        $body = substr($result, $headerSize);

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

}
