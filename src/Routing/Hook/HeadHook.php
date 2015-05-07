<?php

namespace WellRESTed\Routing\Hook;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WellRESTed\Message\NullStream;
use WellRESTed\Routing\MiddlewareInterface;

/**
 * Removes the body of a response to a HEAD request.
 */
class HeadHook implements MiddlewareInterface
{
    public function dispatch(ServerRequestInterface $request, ResponseInterface &$response)
    {
        $method = strtoupper($request->getMethod());
        if ($method === "HEAD") {
            if ($response->getBody()->getSize() !== 0) {
                $response = $response->withBody(new NullStream());
            }
        }
    }
}
