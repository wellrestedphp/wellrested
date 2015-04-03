<?php

namespace WellRESTed\Routing\Route;

interface StaticRouteInterface extends RouteInterface
{
    /**
     * @return string
     */
    public function getPath();
}
