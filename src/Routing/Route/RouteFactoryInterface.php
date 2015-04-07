<?php

namespace WellRESTed\Routing\Route;

use WellRESTed\Routing\RouteTableInterface;

interface RouteFactoryInterface
{
    /**
     * Adds a new route to a route table.
     *
     * This method SHOULD parse $target to determine to the type of route to
     * use and MUST create the route with the provided $middleware.
     *
     * Once the implementation has created the route the route, it MUST
     * the route with $routeTable by calling an appropriate RouteTable::add-
     * method.
     *
     * $extra MAY be passed to route constructors that use an extra option,
     * such as TemplateRoute.
     *
     * This method MAY register any instance implementing
     * WellRESTed\Routing\Route\RouteInterface.
     *
     * @param RouteTableInterface $routeTable Table to add the route to
     * @param string $target Path, prefix, or pattern to match
     * @param mixed $middleware Middleware to dispatch
     * @param mixed $extra Additional options to pass to a route constructor
     */
    public function registerRoute(RouteTableInterface $routeTable, $target, $middleware, $extra = null);
}
