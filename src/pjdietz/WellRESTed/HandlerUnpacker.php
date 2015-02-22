<?php

namespace pjdietz\WellRESTed;

use pjdietz\WellRESTed\Interfaces\RequestInterface;

class HandlerUnpacker
{
    public function unpack($handler, RequestInterface $request = null, array $args = null)
    {
        if (is_callable($handler)) {
            $handler = $handler($request, $args);
        } elseif (is_string($handler)) {
            $handler = new $handler();
        }
        return $handler;
    }
}
