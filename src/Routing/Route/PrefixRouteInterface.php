<?php

namespace WellRESTed\Routing\Route;

interface PrefixRouteInterface extends RouteInterface
{
    /**
     * @return string
     */
    public function getPrefix();
}
