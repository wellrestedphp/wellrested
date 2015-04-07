<?php

namespace WellRESTed\Routing\Route;

use WellRESTed\Routing\RouteTableInterface;

/**
 * Class for creating routes
 */
class RouteFactory implements RouteFactoryInterface
{
    /**
     * The method will determine the most appropriate route subclass to use
     * and will forward the arguments on to the subclass's constructor.
     *
     * - Paths with no special characters will register StaticRoutes
     * - Paths ending with * will register PrefixRoutes
     * - Paths containing URI variables (e.g., {id}) will register TemplateRoutes
     * - Regular exressions will register RegexRoutes
     *
     * @param RouteTableInterface $routeTable Table to add the route to
     * @param string $target Path, prefix, or pattern to match
     * @param mixed $middleware Middleware to dispatch
     * @param mixed $extra Additional options to pass to a route constructor
     */
    public function registerRoute(RouteTableInterface $routeTable, $target, $middleware, $extra = null)
    {
        if ($target[0] === "/") {

            // Possible static, prefix, or template

            // PrefixRoutes end with *
            if (substr($target, -1) === "*") {
                // Remove the trailing *, since the PrefixRoute constructor doesn't expect it.
                $target = substr($target, 0, -1);
                $route = new PrefixRoute($target, $middleware);
                $routeTable->addPrefixRoute($route);
            }

            // TempalateRoutes contain {variable}
            if (preg_match(TemplateRoute::URI_TEMPLATE_EXPRESSION_RE, $target)) {
                $route = new TemplateRoute($target, $middleware, $extra);
                $routeTable->addRoute($route);
            }

            // StaticRoute
            $route = new StaticRoute($target, $middleware);
            $routeTable->addStaticRoute($route);
        }

        // Regex
        $route = new RegexRoute($target, $middleware);
        $routeTable->addRoute($route);
    }
}
