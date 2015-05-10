<?php

namespace WellRESTed\Responder\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WellRESTed\Message\NullStream;
use WellRESTed\MiddlewareInterface;

/**
 * Removes the body of a response to a HEAD request.
 */
class HeadHandler implements MiddlewareInterface
{
    public function dispatch(ServerRequestInterface $request, ResponseInterface $response, $next)
    {
        $method = strtoupper($request->getMethod());
        if ($method === "HEAD") {
            if ($response->getBody()->getSize() !== 0) {
                $response = $response->withBody(new NullStream());
            }
        }
        return $next($request, $response);
    }
}
