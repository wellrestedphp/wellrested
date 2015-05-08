<?php

namespace WellRESTed\Routing\Route;

use WellRESTed\Routing\MethodMap;

/**
 * Class for creating routes
 */
class RouteFactory implements RouteFactoryInterface
{
    /**
     * Creates a route for the given target.
     *
     * - Target with no special characters will create StaticRoutes
     * - Target ending with * will create PrefixRoutes
     * - Target containing URI variables (e.g., {id}) will create TemplateRoutes
     * - Regular exressions will create RegexRoutes
     *
     * @param string $target Route target or target pattern
     * @return RouteInterface
     */
    public function create($target)
    {
        if ($target[0] === "/") {

            // Possible static, prefix, or template

            // PrefixRoutes end with *
            if (substr($target, -1) === "*") {
                return new PrefixRoute($target, new MethodMap());
            }

            // TempalateRoutes contain {variable}
            if (preg_match(TemplateRoute::URI_TEMPLATE_EXPRESSION_RE, $target)) {
                return new TemplateRoute($target, new MethodMap());
            }

            // StaticRoute
            return new StaticRoute($target, new MethodMap());
        }

        // Regex
        return new RegexRoute($target, new MethodMap());
    }
}
