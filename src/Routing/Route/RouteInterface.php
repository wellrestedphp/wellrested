<?php

namespace WellRESTed\Routing\Route;

use WellRESTed\Routing\MiddlewareInterface;

interface RouteInterface extends MiddlewareInterface
{
    /**
     * Examines a path (request target) and returns whether or not the route
     * should handle the request providing the target.
     *
     * If a successful examination also extracts items (such as captures from
     * matching a regular expression), store them to $captures.
     *
     * $captures should have no meaning for calls that return false.
     *
     * @param string $requestTarget
     * @param array $captures
     * @return boolean
     */
    public function matchesRequestTarget($requestTarget, &$captures = null);
}
