<?php

namespace wellrested;

require_once(dirname(__FILE__) . '/Response.inc.php');
require_once(dirname(__FILE__) . '/exceptions/CurlException.inc.php');

// !TODO: Include port in the URI

/**
 * A Request instance contains information relating to the current HTTP request
 * a client sent to the server.
 *
 * Since the main use for this class is to provide info about the one specific
 * request, the class exposes the static getRequest() method for obtaining a
 * singleton Request instance.
 *
 * @property string body       Entity body of the request
 * @property array headers     Associative array of HTTP headers
 * @property array hostname    The Hostname for the request (e.g., google.com)
 * @property string method     HTTP method or verb for the request
 * @property string path       Path component of the URI for the request
 * @property string pathParts  Fragments of the path, delimited by slashes
 * @property array query       Associative array of query parameters
 * @property array uri         Full URI, including protocol, hostname, path, and query
 *
 * @package WellRESTed
 */
class Request {

    /**
     * Entity body of the request
     *
     * @var string
     */
    protected $body;

    /**
     * Associative array of HTTP headers
     *
     * @var array
     */
    protected $headers;

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
     * Protocal for the request (e.g., http, https)
     *
     * @var string
     */
    protected $protocol = 'http';

    /**
     * Associative array of query parameters
     *
     * @var array
     */
    protected $query;

    /**
     * The string value of the full URI. This is reconstructed from the
     * components if requested when unset.
     *
     * @var string
     */
    protected $uri = null;

    /**
     * Singleton instance derived from reading info from Apache.
     *
     * @var Request
     * @static
     */
    static protected $theRequest;


    // -------------------------------------------------------------------------
    // !Accessors

    /**
     * @param string $name
     * @return array|string
     * @throws \Exception
     */
    public function __get($name) {

        switch ($name) {
            case 'body':
                return $this->getBody();
            case 'headers':
                return $this->getHeaders();
            case 'hostname':
                return $this->getHostname();
            case 'method':
                return $this->getMethod();
            case 'path':
                return $this->getPath();
            case 'pathParts':
                return $this->getPathParts();
            case 'protocol':
                return $this->getProtocol();
            case 'query':
                return $this->getQuery();
            case 'uri':
                return $this->getUri();
            default:
                throw new \Exception('Property ' . $name . ' does not exist.');
        }

    } // __get()

    /**
     * @param string $name
     * @param mixed $value
     * @throws \Exception
     */
    public function __set($name, $value) {

        switch ($name) {
            case 'body':
                $this->setBody($value);
                return;
            case 'hostname':
                $this->setHostname($value);
                return;
            case 'method':
                $this->setMethod($value);
                return;
            case 'path':
                $this->setPath($value);
                return;
            case 'protocol':
                $this->setProtocol($value);
                return;
            case 'query':
                $this->setQuery($value);
                return;
            case 'uri':
                $this->setUri($value);
                return;
            default:
                throw new \Exception('Property ' . $name . 'does not exist.');
        }

    }

    /**
     * Return the body payload of the instance.
     *
     * @return string
     */
    public function getBody() {
        return $this->body;
    }

    /**
     * Return an associative array of all set headers.
     *
     * @return array
     */
    public function getHeaders() {
        return $this->headers;
    }

    /**
     * Return the hostname set for the instance.
     *
     * @return array
     */
    public function getHostname() {
        return $this->hostname;
    }

    /**
     * Return the HTTP method (e.g., GET, POST, PUT, DELETE)
     *
     * @return string
     */
    public function getMethod() {
        return $this->method;
    }

    /**
     * Return the protocol (e.g., http, https)
     *
     * @return string
     */
    public function getProtocol() {
        return $this->protocol;
    }

    /**
     * Return the path part of the URI as a string.
     *
     * @return string
     */
    public function getPath() {
        return $this->path;
    }

    /**
     * Return an array of the sections of the path delimited by slashes.
     *
     * @return array
     */
    public function getPathParts() {
        return $this->pathParts;
    }

    /**
     * Return an associative array representing the query.
     *
     * @return array
     */
    public function getQuery() {
        return $this->query;
    }

    /**
     * Return the full URI includeing protocol, hostname, path, and query.
     *
     * @return array
     */
    public function getUri() {

        // Construct the URI if it is unset.
        if (!is_null($this->uri)) {
            $this->rebuildUri();
        }
        return $this->uri;

    }

    /**
     * Set the body for the request.
     *
     * @param string $body
     */
    public function setBody($body) {
        $this->body = $body;
    }

    /**
     * Set the hostname for the request and update the URI.
     *
     * @param string $hostname
     */
    public function setHostname($hostname) {

        $this->hostname = $hostname;

        // Update the URI member.
        $this->rebuildUri();

    }

    /**
     * Set the method for the request.
     *
     * @param string $method
     * @throws \InvalidArgumentException
     */
    public function setMethod($method) {

        if (!is_string($method)) {
            throw new \InvalidArgumentException('method must be a string.');
        }

        $this->method = $method;

    }

    /**
     * Set the path and pathParts members.
     *
     * @param string $path
     */
    public function setPath($path) {

        $this->path = $path;
        $this->pathParts = explode('/', substr($path, 1));

        // Update the URI member.
        $this->rebuildUri();
    }

    /**
     * Set the protocol for the request and update the URI.
     *
     * @param string $protocol
     */
    public function setProtocol($protocol) {

        $this->protocol = $protocol;

        // Update the URI member.
        $this->rebuildUri();

    }

    /**
     * @param string|array $query
     * @throws \InvalidArgumentException
     */
    public function setQuery($query) {

        if (is_string($query)) {
            $qs = $query;
            parse_str($qs, $query);
        }

        if (is_array($query)) {
            $this->query = $query;
        } else {
            throw new \InvalidArgumentException('Unable to parse query string.');
        }

        // Update the URI member.
        $this->rebuildUri();

    }

    /**
     * Set the URI for the Request. This method also sets the path, pathParts,
     * and query.
     *
     * @param string $uri
     */
    public function setUri($uri) {

        $this->uri = $uri;
        $parsed = parse_url($uri);

        $host = isset($parsed['host']) ? $parsed['host'] : '';
        $this->setHostname($host);

        $path = isset($parsed['path']) ? $parsed['path'] : '';
        $this->setPath($path);

        $query = isset($parsed['query']) ? $parsed['query'] : '';
        $this->setQuery($query);

    }

    /**
     * Build the URI member from the other members (path, query, etc.)
     */
    protected function rebuildUri() {

        $uri = $this->protocol . '://' . $this->hostname . $this->path;

        if ($this->query) {
            $uri .= '?' . http_build_query($this->query);
        }

        $this->uri = $uri;

    }

    // -------------------------------------------------------------------------

    /**
     * Make a cURL request out of the instance and return a Response.
     *
     * @return Response
     * @throws exceptions\CurlException
     */
    public function request() {

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

        // Make the cURL request.
        $result = curl_exec($ch);

        // Throw an exception in the event of a cURL error.
        if ($result === false) {
            $error = curl_error($ch);
            $errno = curl_errno($ch);
            curl_close($ch);
            throw new exceptions\CurlException($error, $errno);
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
    public function readHttpRequest() {

        $this->setBody(file_get_contents("php://input"), false);
        $this->headers = apache_request_headers();
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
     public static function getRequest() {

        if (!isset(self::$theRequest)) {

            $klass = __CLASS__;
            $request = new $klass();
            $request->readHttpRequest();

            self::$theRequest = $request;

        }

        return self::$theRequest;

    }

} // Request

?>
