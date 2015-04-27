<?php

namespace WellRESTed\Message;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

class ServerRequest extends Request implements ServerRequestInterface
{
    /** @var array */
    private $attributes;
    /** @var array */
    private $cookieParams;
    /** @var mixed */
    private $parsedBody;
    /** @var array */
    private $queryParams;
    /** @var array */
    private $serverParams;
    /** @var array */
    private $uploadedFiles;

    // ------------------------------------------------------------------------

    public function __construct()
    {
        parent::__construct();
        $this->attributes = [];
        $this->cookieParams = [];
        $this->queryParams = [];
        $this->serverParams = [];
        $this->uploadedFiles = [];
    }

    public function __clone()
    {
        if (is_object($this->parsedBody)) {
            $this->parsedBody = clone $this->parsedBody;
        }
        parent::__clone();
    }

    // ------------------------------------------------------------------------
    // Psr\Http\Message\ServerRequestInterface

    /**
     * Retrieve server parameters.
     *
     * Retrieves data related to the incoming request environment,
     * typically derived from PHP's $_SERVER superglobal. The data IS NOT
     * REQUIRED to originate from $_SERVER.
     *
     * @return array
     */
    public function getServerParams()
    {
        return $this->serverParams;
    }

    /**
     * Retrieve cookies.
     *
     * Retrieves cookies sent by the client to the server.
     *
     * The data MUST be compatible with the structure of the $_COOKIE
     * superglobal.
     *
     * @return array
     */
    public function getCookieParams()
    {
        return $this->cookieParams;
    }

    /**
     * Create a new instance with the specified cookies.
     *
     * The data IS NOT REQUIRED to come from the $_COOKIE superglobal, but MUST
     * be compatible with the structure of $_COOKIE. Typically, this data will
     * be injected at instantiation.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * updated cookie values.
     *
     * @param array $cookies Array of key/value pairs representing cookies.
     * @return self
     */
    public function withCookieParams(array $cookies)
    {
        $request = clone $this;
        $request->cookieParams = $cookies;
        return $request;
    }

    /**
     * Retrieve query string arguments.
     *
     * Retrieves the deserialized query string arguments, if any.
     *
     * Note: the query params might not be in sync with the URL or server
     * params. If you need to ensure you are only getting the original
     * values, you may need to parse the composed URL or the `QUERY_STRING`
     * composed in the server params.
     *
     * @return array
     */
    public function getQueryParams()
    {
        return $this->queryParams;
    }

    /**
     * Create a new instance with the specified query string arguments.
     *
     * These values SHOULD remain immutable over the course of the incoming
     * request. They MAY be injected during instantiation, such as from PHP's
     * $_GET superglobal, or MAY be derived from some other value such as the
     * URI. In cases where the arguments are parsed from the URI, the data
     * MUST be compatible with what PHP's parse_str() would return for
     * purposes of how duplicate query parameters are handled, and how nested
     * sets are handled.
     *
     * Setting query string arguments MUST NOT change the URL stored by the
     * request, nor the values in the server params.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * updated query string arguments.
     *
     * @param array $query Array of query string arguments, typically from
     *     $_GET.
     * @return self
     */
    public function withQueryParams(array $query)
    {
        $request = clone $this;
        $request->queryParams = $query;
        return $request;
    }

    /**
     * Retrieve normalized file upload data.
     *
     * This method returns upload metadata in a normalized tree, with each leaf
     * an instance of Psr\Http\Message\UploadedFileInterface.
     *
     * These values MAY be prepared from $_FILES or the message body during
     * instantiation, or MAY be injected via withUploadedFiles().
     *
     * @return array An array tree of UploadedFileInterface instances; an empty
     *     array MUST be returned if no data is present.
     */
    public function getUploadedFiles()
    {
        return $this->uploadedFiles;
    }

    /**
     * Create a new instance with the specified uploaded files.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated body parameters.
     *
     * @param array An array tree of UploadedFileInterface instances.
     * @return self
     * @throws \InvalidArgumentException if an invalid structure is provided.
     */
    public function withUploadedFiles(array $uploadedFiles)
    {
        if (!$this->isValidUploadedFilesTree($uploadedFiles)) {
            throw new \InvalidArgumentException(
                "withUploadedFiles expects an array with string keys and UploadedFileInterface[] values");
        }

        $request = clone $this;
        $request->uploadedFiles = $uploadedFiles;
        return $request;
    }

    /**
     * Retrieve any parameters provided in the request body.
     *
     * If the request Content-Type is either application/x-www-form-urlencoded
     * or multipart/form-data, and the request method is POST, this method MUST
     * return the contents of $_POST.
     *
     * Otherwise, this method may return any results of deserializing
     * the request body content; as parsing returns structured content, the
     * potential types MUST be arrays or objects only. A null value indicates
     * the absence of body content.
     *
     * @return null|array|object The deserialized body parameters, if any.
     *     These will typically be an array or object.
     */
    public function getParsedBody()
    {
        return $this->parsedBody;
    }

    /**
     * Create a new instance with the specified body parameters.
     *
     * These MAY be injected during instantiation.
     *
     * If the request Content-Type is either application/x-www-form-urlencoded
     * or multipart/form-data, and the request method is POST, use this method
     * ONLY to inject the contents of $_POST.
     *
     * The data IS NOT REQUIRED to come from $_POST, but MUST be the results of
     * deserializing the request body content. Deserialization/parsing returns
     * structured data, and, as such, this method ONLY accepts arrays or objects,
     * or a null value if nothing was available to parse.
     *
     * As an example, if content negotiation determines that the request data
     * is a JSON payload, this method could be used to create a request
     * instance with the deserialized parameters.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * updated body parameters.
     *
     * @param null|array|object $data The deserialized body data. This will
     *     typically be in an array or object.
     * @return self
     */
    public function withParsedBody($data)
    {
        if (!(is_null($data) || is_array($data) || is_object($data))) {
            throw new \InvalidArgumentException("Parsed body must be null, array, or object.");
        }

        $request = clone $this;
        $request->parsedBody = $data;
        return $request;
    }

    /**
     * Retrieve attributes derived from the request.
     *
     * The request "attributes" may be used to allow injection of any
     * parameters derived from the request: e.g., the results of path
     * match operations; the results of decrypting cookies; the results of
     * deserializing non-form-encoded message bodies; etc. Attributes
     * will be application and request specific, and CAN be mutable.
     *
     * @return array Attributes derived from the request.
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Retrieve a single derived request attribute.
     *
     * Retrieves a single derived request attribute as described in
     * getAttributes(). If the attribute has not been previously set, returns
     * the default value as provided.
     *
     * This method obviates the need for a hasAttribute() method, as it allows
     * specifying a default value to return if the attribute is not found.
     *
     * @see getAttributes()
     * @param string $name The attribute name.
     * @param mixed $default Default value to return if the attribute does not exist.
     * @return mixed
     */
    public function getAttribute($name, $default = null)
    {
        if (isset($this->attributes[$name])) {
            return $this->attributes[$name];
        }
        return $default;
    }

    /**
     * Create a new instance with the specified derived request attribute.
     *
     * This method allows setting a single derived request attribute as
     * described in getAttributes().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * updated attribute.
     *
     * @see getAttributes()
     * @param string $name The attribute name.
     * @param mixed $value The value of the attribute.
     * @return self
     */
    public function withAttribute($name, $value)
    {
        $request = clone $this;
        $request->attributes[$name] = $value;
        return $request;
    }

    /**
     * Create a new instance that removes the specified derived request
     * attribute.
     *
     * This method allows removing a single derived request attribute as
     * described in getAttributes().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that removes
     * the attribute.
     *
     * @see getAttributes()
     * @param string $name The attribute name.
     * @return self
     */
    public function withoutAttribute($name)
    {
        $request = clone $this;
        unset($request->attributes[$name]);
        return $request;
    }

    // ------------------------------------------------------------------------

    protected function readFromServerRequest(array $attributes = null)
    {
        $this->attributes = $attributes ?: [];
        $this->serverParams = $_SERVER;
        $this->cookieParams = $_COOKIE;
        $this->readUploadedFiles($_FILES);
        $this->queryParams = [];
        if (isset($_SERVER["QUERY_STRING"])) {
            parse_str($_SERVER["QUERY_STRING"], $this->queryParams);
        }
        if (isset($_SERVER["SERVER_PROTOCOL"]) && $_SERVER["SERVER_PROTOCOL"] === "HTTP/1.0") {
            // The default is 1.1, so only update if 1.0
            $this->protcolVersion = "1.0";
        }
        if (isset($_SERVER["REQUEST_METHOD"])) {
            $this->method = $_SERVER["REQUEST_METHOD"];
        }
        if (isset($_SERVER["REQUEST_URI"])) {
            $this->requestTarget = $_SERVER["REQUEST_URI"];
        }
        $headers = $this->getServerRequestHeaders();
        foreach ($headers as $key => $value) {
            $this->headers[$key] = $value;
        }
        $this->body = $this->getStreamForBody();

        $contentType = $this->getHeader("Content-type");
        if ($contentType === ["application/x-www-form-urlencoded"] || $contentType === ["multipart/form-data"]) {
            $this->parsedBody = $_POST;
        }
    }

    protected function readUploadedFiles($files)
    {
        $uploadedFiles = [];
        foreach ($files as $name => $file) {
            if (is_array($file["name"])) {
                for ($index = 0, $u = count($file["name"]); $index < $u; ++$index) {
                    $uploadedFile = new UploadedFile(
                        $file["name"][$index],
                        $file["type"][$index],
                        $file["size"][$index],
                        $file["tmp_name"][$index],
                        $file["error"][$index]
                    );
                    $uploadedFiles[$name][$index] = $uploadedFile;
                }
            } else {
                $index = 0;
                $uploadedFile = new UploadedFile(
                    $file["name"], $file["type"], $file["size"], $file["tmp_name"], $file["error"]
                );
                $uploadedFiles[$name][$index] = $uploadedFile;
            }
        }
        $this->uploadedFiles = $uploadedFiles;
    }

    /**
     * Return a reference to the singleton instance of the Request derived
     * from the server's information about the request sent to the server.
     *
     * @return self
     * @static
     */
    public static function getServerRequest(array $attributes = null)
    {
        $request = new static();
        $request->readFromServerRequest($attributes);
        return $request;
    }

    /**
     * Return a stream representing the request's body.
     *
     * Override this method to use a specific StreamInterface implementation.
     *
     * @return StreamInterface
     */
    protected function getStreamForBody()
    {
        return new Stream(fopen("php://input", "r"));
    }

    /**
     * Read and return all request headers from the request issued to the server.
     *
     * @return array Associative array of headers
     */
    protected function getServerRequestHeaders()
    {
        // http://www.php.net/manual/en/function.getallheaders.php#84262
        $headers = array();
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) === "HTTP_") {
                $headers[str_replace(" ", "-", ucwords(strtolower(str_replace("_", " ", substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }

    private function isValidUploadedFilesTree(array $uploadedFiles)
    {
        // Ensure all keys are strings.
        $keys = array_keys($uploadedFiles);
        if (count($keys) !== count(array_filter($keys, "is_string"))) {
            return false;
        }

        // All values must be UploadedFileInterface[].

        // Ensure all values are arrays.
        $values = array_values($uploadedFiles);
        if (count($values) !== count(array_filter($values, "is_array"))) {
            return false;
        }

        $isUploadedFileInterface = function ($object) {
            return is_object($object) && in_array('Psr\Http\Message\UploadedFileInterface', class_implements($object));
        };

        foreach ($values as $items) {

            // Ensure values are list arrays.
            if (array_keys($items) !== range(0, count($items) - 1)) {
                return false;
            }

            // Ensure all items are UploadedFileInterfaces
            $itemValues = array_values($items);
            if (count($itemValues) !== count(array_filter($itemValues, $isUploadedFileInterface))) {
                return false;
            }
        }

        return true;
    }

}
