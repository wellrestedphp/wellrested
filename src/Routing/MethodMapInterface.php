<?php

namespace WellRESTed\Routing;

interface MethodMapInterface
{
    /**
     * @param array $map
     */
    public function addMap($map);

    /**
     * @param string $method
     * @param mixed $middleware
     */
    public function add($method, $middleware);
}
