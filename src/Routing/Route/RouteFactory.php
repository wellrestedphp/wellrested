<?php

namespace WellRESTed\Routing\Route;

use WellRESTed\Dispatching\DispatcherInterface;

/**
 * @internal
 */
class RouteFactory
{
    private $dispatcher;

    public function __construct(DispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Creates a route for the given target.
     *
     * - Target with no special characters will create StaticRoutes
     * - Target ending with * will create PrefixRoutes
     * - Target containing URI variables (e.g., {id}) will create TemplateRoutes
     * - Regular expressions will create RegexRoutes
     *
     * @param string $target Route target or target pattern
     * @return Route
     */
    public function create(string $target): Route
    {
        if ($target[0] === '/') {
            // Possible static, prefix, or template

            // PrefixRoutes end with *
            if (substr($target, -1) === '*') {
                return new PrefixRoute($target, new MethodMap($this->dispatcher));
            }

            // TemplateRoutes contain {variable}
            if (preg_match(TemplateRoute::URI_TEMPLATE_EXPRESSION_RE, $target)) {
                return new TemplateRoute($target, new MethodMap($this->dispatcher));
            }

            // StaticRoute
            return new StaticRoute($target, new MethodMap($this->dispatcher));
        }

        // Regex
        return new RegexRoute($target, new MethodMap($this->dispatcher));
    }
}
