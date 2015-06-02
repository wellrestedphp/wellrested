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
     * propagation; returning a response without calling $next halts
     * propagation and prevents subsequent middleware from running.
     *
     * Implementations SHOULD call $next to allow subsequent middleware to act
     * on the request and response. Implementations MAY further alter the
     * response returned by $next before returning it.
     *
     * Implementations MAY return a response without calling $next to prevent
     * propagation (e.g., for error conditions).
     *
     * Implementations SHOULD NOT call $next and disregard the response by
     * returning an entirely unrelated response.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable $next
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next);
}
