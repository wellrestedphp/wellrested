<?php

/**
 * pjdietz\WellRESTed\HandlerUnpacker
 *
 * @author PJ Dietz <pj@pjdietz.com>
 * @copyright Copyright 2015 by PJ Dietz
 * @license MIT
 */

namespace pjdietz\WellRESTed;

use pjdietz\WellRESTed\Interfaces\RequestInterface;

/**
 * Class for retreiving a handler or response from a callable, string, or instance.
 */
class HandlerUnpacker
{
    /**
     * Return the handler or response from a callable, string, or instance.
     *
     * @param $handler
     * @param RequestInterface $request
     * @param array $args
     * @return mixed
     */
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
