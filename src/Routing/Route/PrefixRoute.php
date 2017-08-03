<?php

namespace WellRESTed\Routing\Route;

class PrefixRoute extends Route
{
    public function __construct($target, $methodMap)
    {
        parent::__construct(rtrim($target, "*"), $methodMap);
    }

    public function getType()
    {
        return RouteInterface::TYPE_PREFIX;
    }

    /**
     * Examines a request target to see if it is a match for the route.
     *
     * @param string $requestTarget
     * @return boolean
     */
    public function matchesRequestTarget($requestTarget)
    {
        return strrpos($requestTarget, $this->target, -strlen($requestTarget)) !== false;
    }

    /**
     * Always returns an empty array.
     */
    public function getPathVariables()
    {
        return [];
    }
}
