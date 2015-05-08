<?php

namespace WellRESTed\Routing\Route;

interface RouteFactoryInterface
{
    /**
     * Creates a route for the given target.
     */
    public function create($target);
}
