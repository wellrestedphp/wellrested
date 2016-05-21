<?php

namespace WellRESTed\Test\Doubles;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WellRESTed\MiddlewareInterface;

class MiddlewareMock implements MiddlewareInterface
{
    public $called = false;
    public $callCount = 0;
    public $request = null;
    public $response = null;
    public $propagate = true;

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        $next
    ) {
        $this->called = true;
        $this->callCount++;
        $this->request = $request;
        $this->response = $response;
        if ($this->propagate) {
            return $next($request, $response);
        } else {
            return $response;
        }
    }
}
