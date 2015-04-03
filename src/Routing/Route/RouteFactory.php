<?php

namespace WellRESTed\Routing\Route;

use ReflectionClass;
use WellRESTed\Routing\RouteTableInterface;

/**
 * Class for creating routes
 */
class RouteFactory
{
    /** @var RouteTableInterface */
    private $table;

    public function __construct(RouteTableInterface $table)
    {
        $this->table = $table;
    }

    /**
     * Create and return a route given a string path, a handler, and optional
     * extra arguments.
     *
     * The method will determine the most appropriate route subclass to use
     * and will forward the arguments on to the subclass's constructor.
     *
     * - Paths with no special characters will generate StaticRoutes
     * - Paths ending with * will generate PrefixRoutes
     * - Paths containing URI variables (e.g., {id}) will generate TemplateRoutes
     * - Regular exressions will generate RegexRoutes
     *
     * @param string $target Path, prefix, or pattern to match
     * @param mixed $middleware Middleware to dispatch
     * @param $defaultPattern @see TemplateRoute
     * @param $variablePatterns @see TemplateRoute
     */
    public function registerRoute($target, $middleware, $defaultPattern = null, $variablePatterns = null)
    {
        if ($target[0] === "/") {

            // Possible static, prefix, or template

            // PrefixRoutes end with *
            if (substr($target, -1) === "*") {
                // Remove the trailing *, since the PrefixRoute constructor doesn't expect it.
                $target = substr($target, 0, -1);
                $route = new PrefixRoute($target, $middleware);
                $this->table->addPrefixRoute($route);
            }

            // TempalateRoutes contain {variable}
            if (preg_match(TemplateRoute::URI_TEMPLATE_EXPRESSION_RE, $target)) {
                $route = new TemplateRoute($target, $middleware, $defaultPattern, $variablePatterns);
                $this->table->addRoute($route);
            }

            // StaticRoute
            $route = new StaticRoute($target, $middleware);
            $this->table->addStaticRoute($route);
        }

        // Regex
        $route = new RegexRoute($target, $middleware);
        $this->table->addRoute($route);
    }
}
