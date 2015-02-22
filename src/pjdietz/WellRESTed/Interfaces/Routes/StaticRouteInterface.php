<?php

/**
 * pjdietz\WellRESTed\Interfaces\Route\StaticRouteInterface
 *
 * @author PJ Dietz <pj@pjdietz.com>
 * @copyright Copyright 2015 by PJ Dietz
 * @license MIT
 */

namespace pjdietz\WellRESTed\Interfaces\Routes;

/**
 * Interface for routes that map to an exact path or paths
 */
interface StaticRouteInterface
{
    /**
     * Returns the paths this maps to a target handler.
     *
     * @return array Array of paths.
     */
    public function getPaths();
}
