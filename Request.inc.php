<?php

namespace wellrested;

/*******************************************************************************
 * Request
 *
 * A Request instance contains information relating to the current HTTP request
 * a client sent to the server.
 *
 * Since the main use for this class is to provide info about the one specific
 * request, the class exposes the static getRequest() method for obtaining a
 * singleton Request instance.
 *
 * @package WellRESTed
 *
 ******************************************************************************/

/**
 * @property string body       Entity body of the request
 * @property array headers     Associative array of HTTP headers
 * @property string method     HTTP method or verb for the request
 * @property string path       Path component of the URI for the request
 * @property string pathParts  Fragments of the path, delimited by slashes
 * @property array query       Associative array of query parameters
 */
 class Request {

    /**
     * Entity body of the request
     * @var string
     */
    protected $body;

    /**
     * Associative array of HTTP headers
     * @var array
     */
    protected $headers;

    /**
     * HTTP method or verb for the request
     * @var string
     */
    protected $method;

    /**
     * Path component of the URI for the request
     * @var string
     */
    protected $path;

    /**
     * Array of fragments of the path, delimited by slashes
     * @var array
     */
    protected $pathParts;

    /**
     * Associative array of query parameters
     * @var array
     */
    protected $query;

    /**
     * Singleton instance derived from reading info from Apache.
     * @var Request
     * @static
     */
    static protected $theRequest;


    // -------------------------------------------------------------------------
    // !Accessors

    public function __get($name) {

        switch ($name) {
        case 'body':
            return $this->getBody();
        case 'headers':
            return $this->getHeaders();
        case 'method':
            return $this->getMethod();
        case 'path':
            return $this->getPath();
        case 'pathParts':
            return $this->getPathParts();
        case 'query':
            return $this->getQuery();
        default:
            throw new Exception('Property ' . $name . ' does not exist.');
        }

    } // __get()

    public function getBody() {
        return $this->body;
    }

    public function getHeaders() {
        return $this->headers;
    }

    public function getMethod() {
        return $this->method;
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


    // -------------------------------------------------------------------------

    /**
     * Set instance members based on the HTTP request sent to the server.
     */
    public function readHttpRequest() {

        $this->body = file_get_contents("php://input");

        $this->headers = apache_request_headers();

        $this->method = $_SERVER['REQUEST_METHOD'];

        $uri = parse_url($_SERVER['REQUEST_URI']);
        $this->path = $uri['path'];

        $this->pathParts = explode('/', substr($this->path, 1));

        $this->query = $_GET;

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
