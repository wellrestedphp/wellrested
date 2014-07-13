<?php

use pjdietz\WellRESTed\Interfaces\HandlerInterface;
use pjdietz\WellRESTed\Response;

/**
 * Mini Handler class that allways returns a 200 status code Response.
 */
class MockHandler implements HandlerInterface
{
    public function getResponse(\pjdietz\WellRESTed\Interfaces\RequestInterface $request, array $args = null)
    {
        $resp = new Response();
        $resp->setStatusCode(200);
        return $resp;
    }
}
