<?php

namespace WellRESTed\Routing\Route;

class PrefixRoute extends Route
{
    public function __construct($target, $methodMap)
    {
        $this->target = rtrim($target, "*");
        $this->methodMap = $methodMap;
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
}
