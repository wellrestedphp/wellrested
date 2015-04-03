<?php

namespace WellRESTed\Routing\Route;

class StaticRoute extends Route implements StaticRouteInterface
{
    private $path;

    public function __construct($path, $middleware)
    {
        parent::__construct($middleware);
        $this->path = $path;
    }

    /**
     * @param string $requestTarget
     * @param array $captures
     * @return bool
     */
    public function matchesRequestTarget($requestTarget, &$captures = null)
    {
        return $requestTarget == $this->path;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }
}
