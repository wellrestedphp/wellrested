<?php

namespace wellrested;

require_once(dirname(__FILE__) . '/Request.inc.php');
require_once(dirname(__FILE__) . '/Response.inc.php');

class Handler {

    protected $request;
    protected $response;
    protected $matches;

    public function __construct($request, $matches=null) {

        $this->request = $request;

        if (is_null($matches)) {
            $matches = array();
        }
        $this->matches = $matches;

        $this->response = new Response();
        $this->buildResponse();

    }

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

    public function getResponse() {
        return $this->response;
    }

    protected function get() {
        $this->response->statusCode = 405;
    }

    protected function head() {
        $this->response->statusCode = 405;
    }

    protected function post() {
        $this->response->statusCode = 405;
    }

    protected function put() {
        $this->response->statusCode = 405;
    }

    protected function patch() {
        $this->response->statusCode = 405;
    }

    protected function options() {
        $this->response->statusCode = 405;
    }

}


?>
