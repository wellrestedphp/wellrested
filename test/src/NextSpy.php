<?php

namespace WellRESTed\Test;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class NextSpy
{
    public $called = false;
    public $request = null;
    public $response = null;

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $respone
    ) {
        $this->called = true;
        $this->request = $request;
        $this->response = $respone;
        return $respone;
    }
}
