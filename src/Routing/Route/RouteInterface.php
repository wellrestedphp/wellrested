<?php

namespace WellRESTed\Routing\Route;

use WellRESTed\MiddlewareInterface;
use WellRESTed\Routing\MethodMapInterface;

interface RouteInterface extends MiddlewareInterface
{
    const TYPE_STATIC = 0;
    const TYPE_PREFIX = 1;
    const TYPE_PATTERN = 2;

    public function getTarget();

    public function getType();

    /**
     * Return the instance mapping methods to middleware for this route.
     *
     * @return MethodMapInterface
     */
    public function getMethodMap();

    /**
     * Examines a path (request target) to see if it is a match for the route.
     *
     * @return boolean
     */
    public function matchesRequestTarget($requestTarget);
}
