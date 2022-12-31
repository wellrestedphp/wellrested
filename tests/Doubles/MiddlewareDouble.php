<?php

namespace WellRESTed\Test\Doubles;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WellRESTed\MiddlewareInterface;

class MiddlewareDouble implements MiddlewareInterface
{
    public bool $called = false;
    public int $callCount = 0;
    public ?ServerRequestInterface $request = null;
    public ?ResponseInterface $response = null;
    public bool $propagate = true;

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
