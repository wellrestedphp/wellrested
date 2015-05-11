<?php

namespace WellRESTed\Transmission\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WellRESTed\MiddlewareInterface;

/**
 * Adds a Content-length header to the response when all of these are true:
 *
 * - Response does not have a Content-length header
 * - Response does not have a Tranfser-encoding: chunked header
 * - Response body stream reports a size
 */
class ContentLengthHandler implements MiddlewareInterface
{
    public function dispatch(ServerRequestInterface $request, ResponseInterface $response, $next)
    {
        if ($response->hasHeader("Content-length")) {
            return $next($request, $response);
        }
        if (strtolower($response->getHeaderLine("Transfer-encoding")) === "chunked") {
            return $next($request, $response);
        }
        $size = $response->getBody()->getSize();
        if ($size !== null) {
            $response = $response->withHeader("Content-length", (string) $size);
        }
        return $next($request, $response);
    }
}
