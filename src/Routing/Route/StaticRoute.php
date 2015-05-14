<?php

namespace WellRESTed\Routing\Route;

class StaticRoute extends Route
{
    public function getType()
    {
        return RouteInterface::TYPE_STATIC;
    }

    /**
     * Examines a request target to see if it is a match for the route.
     *
     * @param string $requestTarget
     * @return boolean
     */
    public function matchesRequestTarget($requestTarget)
    {
        return $requestTarget === $this->getTarget();
    }

    /**
     * Always returns an empty array.
     */
    public function getPathVariables()
    {
        return [];
    }
}
