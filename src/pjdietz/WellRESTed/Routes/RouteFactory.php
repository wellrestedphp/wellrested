<?php

/**
 * pjdietz\WellRESTed\RouteCreator
 *
 * @author PJ Dietz <pj@pjdietz.com>
 * @copyright Copyright 2015 by PJ Dietz
 * @license MIT
 */

namespace pjdietz\WellRESTed\Routes;

use pjdietz\WellRESTed\Interfaces\HandlerInterface;
use ReflectionClass;

/**
 * Class for creating routes
 */
class RouteFactory
{
    /**
     * @return HandlerInterface
     */
    public function createRoute()
    {
        $args = func_get_args();
        $path = $args[0];

        if ($path[0] === "/") {

            // Possible static, prefix, or template

            // PrefixRoutes end with *
            if (substr($path, -1) === "*") {
                // Remove the trailing *, since the PrefixRoute constructor doesn't expect it.
                $path = substr($path, 0, -1);
                $constructorArgs = $args;
                $constructorArgs[0] = $path;
                $reflector = new ReflectionClass("\\pjdietz\\WellRESTed\\Routes\\PrefixRoute");
                return $reflector->newInstanceArgs($constructorArgs);
            }

            // TempalateRoutes contain {variable}
            if (preg_match(TemplateRoute::URI_TEMPLATE_EXPRESSION_RE, $path)) {
                $reflector = new ReflectionClass("\\pjdietz\\WellRESTed\\Routes\\TemplateRoute");
                return $reflector->newInstanceArgs($args);
            }

            // StaticRoute
            $reflector = new ReflectionClass("\\pjdietz\\WellRESTed\\Routes\\StaticRoute");
            return $reflector->newInstanceArgs($args);

        }

        // Regex
        $reflector = new ReflectionClass("\\pjdietz\\WellRESTed\\Routes\\RegexRoute");
        return $reflector->newInstanceArgs($args);
    }
}
