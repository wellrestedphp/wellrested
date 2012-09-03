<?php

namespace wellrested;

require_once(dirname(__FILE__) . '/Request.inc.php');
require_once(dirname(__FILE__) . '/Response.inc.php');

class Handler {

    protected $request;
    protected $response;
    protected $matches;

    public function __construct($matches=null) {

        if (is_null($matches)) {
            $matches = array();
        }
        $this->matches = $matches;

        $this->request = Request::getRequest();
        $this->response = new Response();

    }

    public function respond() {

        switch ($this->request->method) {
        case 'GET':

        case 'HEAD':

        case 'POST':

        case 'PUT':

        case 'DELETE':

        case 'PATCH':

        case 'OPTIONS':


        }

        $this->response->body = 'Do stuff' . "\n";
        $this->response->body = print_r($this->matches, true);
        $this->response->statusCode = 200;
        $this->response->respond();

    }

}


?>
