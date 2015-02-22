<?php

/**
 * pjdietz\WellRESTed\Interfaces\Route\PrefixRouteInterface
 *
 * @author PJ Dietz <pj@pjdietz.com>
 * @copyright Copyright 2015 by PJ Dietz
 * @license MIT
 */

namespace pjdietz\WellRESTed\Interfaces\Routes;

/**
 * Interface for routes that map to paths begining with a given prefix or prefixes
 */
interface PrefixRouteInterface
{
    /**
     * Returns the path prefixes this maps to a target handler.
     *
     * @return array Array of path prefixes.
     */
    public function getPrefixes();
}
