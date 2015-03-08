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
     * Returns the paths the instance maps to a target handler.
     *
     * @return string[] List array of paths.
     */
    public function getPaths();
}
