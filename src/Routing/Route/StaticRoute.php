<?php

namespace WellRESTed\Routing\Route;

class StaticRoute extends Route
{
    public function getType()
    {
        return RouteInterface::TYPE_STATIC;
    }

    /**
     * Examines a path (request target) to see if it is a match for the route.
     *
     * @return boolean
     */
    public function matchesRequestTarget($requestTarget)
    {
        return $requestTarget === $this->getTarget();
    }
}
