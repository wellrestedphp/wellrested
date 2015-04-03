<?php

namespace WellRESTed\Routing;

use WellRESTed\Routing\Route\PrefixRouteInterface;
use WellRESTed\Routing\Route\RouteInterface;
use WellRESTed\Routing\Route\StaticRouteInterface;

interface RouteTableInterface
{
    public function addRoute(RouteInterface $route);

    public function addStaticRoute(StaticRouteInterface $staticRoute);

    public function addPrefixRoute(PrefixRouteInterface $prefxRoute);
}
