<?php

/**
 * pjdietz\WellRESTed\Request
 *
 * @author PJ Dietz <pj@pjdietz.com>
 * @copyright Copyright 2014 by PJ Dietz
 * @license MIT
 */

namespace pjdietz\WellRESTed;

use InvalidArgumentException;
use pjdietz\WellRESTed\Interfaces\RequestInterface;
use UnexpectedValueException;

/**
 * A Request instance represents an HTTP request. This class has two main uses:
 *
 * First, you can access a singleton instance via the getRequest() method that
 * represents the request sent to the server. The instance will contain the URI,
 * headers, body, etc.
 *
 * Second, you can create a custom Request and use it to obtain a Response
 * from a server through cURL.
 */
class Request extends Message implements RequestInterface
{
    /**
     * Singleton instance derived from reading info from Apache.
     *
     * @var Request
     * @static
     */
    static protected $theRequest;
    /** @var string  HTTP method or verb for the request */
    private $method = "GET";
    /** @var string Scheme for the request (Must be "http" or "https" */
    private $scheme;
    /** @var string  The Hostname for the request (e.g., www.google.com) */
    private $hostname = "localhost";
    /** @var string   Path component of the URI for the request */
    private $path = "/";
    /** @var array Array of fragments of the path, delimited by slashes */
    private $pathParts;
    /** @var int HTTP Port */
    private $port = 80;
    /** @var array Associative array of query parameters */
    private $query;

    // -------------------------------------------------------------------------

    /**
     * Create a new Request instance.
     *
     * @param string|null $uri
     * @param string $method
     */
    public function __construct($uri = null, $method = "GET")
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

    /**
     * Read and return all request headers from the request issued to the server.
     *
     * @return array Associative array of headers
     */
    public static function getRequestHeaders()
    {
        // http://www.php.net/manual/en/function.getallheaders.php#84262
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) === "HTTP_") {
                $headers[str_replace(" ", "-", ucwords(strtolower(str_replace("_", " ", substr($name, 5)))))] = $value;
            }
        }
        return $headers;
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

        $this->setMethod($_SERVER["REQUEST_METHOD"]);
        $this->setUri($_SERVER["REQUEST_URI"]);
        $this->setHostname($_SERVER["HTTP_HOST"]);
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
     * Return the full URI includeing protocol, hostname, path, and query.
     *
     * @return array
     */
    public function getUri()
    {
        $uri = $this->scheme . "://" . $this->hostname;
        if ($this->port !== $this->getDefaultPort()) {
            $uri .= ":" . $this->port;
        }
        if ($this->path !== "/") {
            $uri .= $this->path;
        }
        if ($this->query) {
            $uri .= "?" . http_build_query($this->query);
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
        // Provide http and localhost if missing.
        if ($uri[0] === "/") {
            $uri = "http://localhost" . $uri;
        } elseif (strpos($uri, "://") === false) {
            $uri = "http://" . $uri;
        }

        $parsed = parse_url($uri);

        $scheme = isset($parsed["scheme"]) ? $parsed["scheme"] : "http";
        $this->setScheme($scheme);

        $host = isset($parsed["host"]) ? $parsed["host"] : "localhost";
        $this->setHostname($host);

        $port = isset($parsed["port"]) ? (int) $parsed["port"] : $this->getDefaultPort();
        $this->setPort($port);

        $path = isset($parsed["path"]) ? $parsed["path"] : "/";
        $this->setPath($path);

        $query = isset($parsed["query"]) ? $parsed["query"] : "";
        $this->setQuery($query);
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
     * Set the scheme for the request (either "http" or "https")
     *
     * @param string $scheme
     * @throws \UnexpectedValueException
     */
    public function setScheme($scheme)
    {
        $scheme = strtolower($scheme);
        if (!in_array($scheme, array("http", "https"))) {
            throw new UnexpectedValueException('Scheme must be "http" or "https".');
        }
        $this->scheme = $scheme;
    }

    /**
     * Return the scheme for the request (either "http" or "https")
     *
     * @return string
     */
    public function getScheme()
    {
        return $this->scheme;
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
        if ($path !== "/") {
            $this->pathParts = explode("/", substr($path, 1));
        } else {
            $this->pathParts = array();
        }
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
     * Return the HTTP port
     *
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * Set the HTTP port
     *
     * @param int $port
     */
    public function setPort($port = null)
    {
        if (is_null($port)) {
            $port = $this->getDefaultPort();
        }
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
     * @throws InvalidArgumentException
     */
    public function setQuery($query)
    {
        if (is_string($query)) {
            $qs = $query;
            parse_str($qs, $query);
        } elseif (is_object($query)) {
            $query = (array) $query;
        }

        if (is_array($query)) {
            ksort($query);
            $this->query = $query;
        } else {
            throw new InvalidArgumentException("Unable to parse query string.");
        }
    }

    /**
     * Return the form fields for this request.
     *
     * @return array
     */
    public function getFormFields()
    {
        parse_str($this->getBody(), $fields);
        return $fields;
    }

    /**
     * Set the body by supplying an associative array of form fields.
     *
     * In additon, add a "Content-type: application/x-www-form-urlencoded" header
     *
     * @param array $fields
     */
    public function setFormFields(array $fields)
    {
        $this->setBody(http_build_query($fields));
        $this->setHeader("Content-type", "application/x-www-form-urlencoded");
    }

    // -------------------------------------------------------------------------

    /**
     * Return the default port for the currently set scheme.
     *
     * @return int;
     */
    protected function getDefaultPort()
    {
        return $this->scheme === "http" ? 80 : 443;
    }
}
