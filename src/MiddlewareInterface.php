<?php

namespace WellRESTed;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface MiddlewareInterface
{
    /**
     * Accepts a request and response and returns a modified response.
     *
     * $request represents the request issued by the client.
     *
     * $response represents the current state of the response.
     *
     * $next is a callable that expects a request and response as parameters
     * and returns a response. Calling $next forwards a request and response
     * to the next middleware in the sequence (if any) and continues
     * propagation; returning a response without calling $next halts propgation
     * and prevents subsequent middleware from running.
     *
     * Implementations MAY call $next to continue propagation. After calling
     * $next, implementations MUST return the response returned by $next or
     * use $next's returned response to determine the response it will
     * ulitimately return. Implementations MUST NOT call $next and disregard
     * $next's returned response.
     *
     * Implementaitons MAY return a response without calling $next to halt
     * propagation.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable $next
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next);
}
