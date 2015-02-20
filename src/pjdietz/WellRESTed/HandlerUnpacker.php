<?php

namespace pjdietz\WellRESTed;

use pjdietz\WellRESTed\Interfaces\HandlerInterface;

class HandlerUnpacker
{
    public function unpack($handler)
    {
        if (is_callable($handler)) {
            $handler = $handler();
        } elseif (is_string($handler)) {
            $handler = new $handler();
        }
        if (!$handler instanceof HandlerInterface) {
            throw new \UnexpectedValueException("Handler must implement HandlerInterface");
        }
        return $handler;
    }
}
