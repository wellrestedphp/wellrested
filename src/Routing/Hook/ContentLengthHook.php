<?php

namespace WellRESTed\Routing\Hook;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WellRESTed\Routing\MiddlewareInterface;

/**
 * Adds a Content-length header to the response when all of these are true:
 *
 * - Response does not have a Content-length header
 * - Response does not have a Tranfser-encoding: chunked header
 * - Response body stream reports a size
 */
class ContentLengthHook implements MiddlewareInterface
{
    public function dispatch(ServerRequestInterface $request, ResponseInterface &$response)
    {
        if ($response->hasHeader("Content-length")) {
            return;
        }
        if (strtolower($response->getHeaderLine("Transfer-encoding")) === "chunked") {
            return;
        }
        $size = $response->getBody()->getSize();
        if ($size !== null) {
            $response = $response->withHeader("Content-length", (string) $size);
        }
    }
}
