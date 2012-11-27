<?php

namespace wellrested;

require_once(dirname(__FILE__)  . '/Response.inc.php');

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
 * @property string method     HTTP method or verb for the request
 * @property string path       Path component of the URI for the request
 * @property string pathParts  Fragments of the path, delimited by slashes
 * @property array query       Associative array of query parameters
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
    protected $method;

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

    public function getBody() {
        return $this->body;
    }

    public function getHeaders() {
        return $this->headers;
    }

    public function getHostname() {
        return $this->hostname;
    }

    public function getMethod() {
        return $this->method;
    }

    public function getProtocol() {
        return $this->rotocol;
    }

    public function getPath() {
        return $this->path;
    }

    public function getPathParts() {
        return $this->pathParts;
    }

    public function getQuery() {
        return $this->query;
    }

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

    public function request() {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->uri);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

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

        $result = curl_exec($ch);

        $resp = new Response();

        if ($result !== false) {

            $resp->body = $result;
            $resp->statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            // TODO: Read headers

        }

        // TODO: Account for error

        curl_close($ch);

        return $resp;

    }

    /**
     * Set instance members based on the HTTP request sent to the server.
     */
    public function readHttpRequest() {

        $this->body = file_get_contents("php://input");
        $this->headers = apache_request_headers();
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->uri = $_SERVER['REQUEST_URI'];
        $this->hostname = $_SERVER['HTTP_HOST'];

    } // readHttpRequest()

    /**
     * Return a reference to the singleton instance of the Request derived
     * from the server's information about the request sent to the script.
     *
     * @return Request
     * @static
     */
    static public function getRequest() {

        if (!isset(self::$theRequest)) {

            $klass = __CLASS__;
            $request = new $klass();
            $request->readHttpRequest();

            self::$theRequest = $request;

        }

        return self::$theRequest;

    } // getRequest()

} // Request

?>
