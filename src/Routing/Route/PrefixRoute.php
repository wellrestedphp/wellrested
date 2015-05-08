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
     * @param string $requestTarget
     * @param array $captures
     * @return bool
     */
    public function matchesRequestTarget($requestTarget, &$captures = null)
    {
        return strrpos($requestTarget, $this->target, -strlen($requestTarget)) !== false;
    }
}
