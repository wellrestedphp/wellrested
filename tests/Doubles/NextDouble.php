<?php

namespace WellRESTed\Test\Doubles;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class NextDouble
{
    public bool $called = false;
    public ?ServerRequestInterface $request = null;
    public ?ResponseInterface $response = null;
    public ?ResponseInterface $upstreamResponse = null;

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response
    ) {
        $this->called = true;
        $this->request = $request;
        $this->response = $response;
        if ($this->upstreamResponse) {
            return $this->upstreamResponse;
        } else {
            return $response;
        }
    }
}
