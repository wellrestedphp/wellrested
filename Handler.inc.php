<?php

namespace wellrested;

require_once(dirname(__FILE__) . '/Request.inc.php');
require_once(dirname(__FILE__) . '/Response.inc.php');

/*******************************************************************************
 * Handler
 *
 * A Handler issues a response for a given resource.
 *
 * @package WellRESTed
 *
 ******************************************************************************/

/**
 * @property Response response  The Response to the request
 */
class Handler {

    /**
     * The HTTP request to respond to.
     * @var Request
     */
    protected $request;

    /**
     * The HTTP response to send based on the request.
     * @var Response
     */
    protected $response;

    /**
     * Matches array from the preg_match() call used to find this Handler.
     * @var array
     */
    protected $args;

    /**
     * Create a new Handler for a specific request.
     *
     * @param Request $request
     * @param array $args
     */
    public function __construct($request, $args=null) {

        $this->request = $request;

        if (is_null($args)) {
            $args = array();
        }
        $this->args = $args;

        $this->response = new Response();
        $this->buildResponse();

    }

    // -------------------------------------------------------------------------
    // !Accessors

    /**
     * @param $name
     * @return Response
     * @throws \Exception
     */
    public function __get($name) {

        switch ($name) {
        case 'response':
            return $this->getResponse();
        default:
            throw new \Exception('Property ' . $name . ' does not exist.');
        }

    }

    /**
     * @return Response
     */
    public function getResponse() {
        return $this->response;
    }

    /**
     * Prepare the Response. Override this method if your subclass needs to
     * repond to any non-standard HTTP methods. Otherwise, override the
     * get, post, put, etc. methods.
     */
    protected function buildResponse() {

        switch ($this->request->method) {

        case 'GET':
            $this->get();
            break;

        case 'HEAD':
            $this->head();
            break;

        case 'POST':
            $this->post();
            break;

        case 'PUT':
            $this->put();
            break;

        case 'DELETE':
            $this->delete();
            break;

        case 'PATCH':
            $this->patch();
            break;

        case 'OPTIONS':
            $this->options();
            break;

        }

    }

    // -------------------------------------------------------------------------
    // !HTTP Methods

    // Each of these methods corresponds to a standard HTTP method. Each method
    // has no arguments and returns nothing, but should affect the instance's
    // response member.
    //
    // By default, the methods will provide a 405 Method Not Allowed header.

    /**
     * Method for handling HTTP GET requests.
     */
    protected function get() {
        $this->response->statusCode = 405;
    }

    /**
     * Method for handling HTTP HEAD requests.
     */
    protected function head() {

        // The default function calls the instance's get() method, then sets
        // the resonse's body member to an empty string.

        $this->get();

        if ($this->response->statusCode == 200) {
            $this->response->setBody('', false);
        }

    }

    /**
     * Method for handling HTTP POST requests.
     */
    protected function post() {
        $this->response->statusCode = 405;
    }

    /**
     * Method for handling HTTP PUT requests.
     */
    protected function put() {
        $this->response->statusCode = 405;
    }

    /**
     * Method for handling HTTP DELETE requests.
     */
    protected function delete() {
        $this->response->statusCode = 405;
    }

    /**
     * Method for handling HTTP PATCH requests.
     */
    protected function patch() {
        $this->response->statusCode = 405;
    }

    /**
     * Method for handling HTTP OPTION requests.
     */
    protected function options() {
        $this->response->statusCode = 405;
    }

}

?>
