<?php

declare(strict_types=1);

namespace WellRESTed\Routing\Route;

use WellRESTed\Server;
use WellRESTed\ServerReferenceTrait;

/**
 * @internal
 */
class RouteFactory
{
    use ServerReferenceTrait;

    public function __construct(Server $server)
    {
        $this->setServer($server);
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
        $server = $this->getServer();

        if ($target[0] === '/') {
            // Possible static, prefix, or template

            // PrefixRoutes end with *
            if (substr($target, -1) === '*') {
                return new PrefixRoute($target, new MethodMap($server));
            }

            // TemplateRoutes contain {variable}
            if (preg_match(TemplateRoute::URI_TEMPLATE_EXPRESSION_RE, $target)) {
                return new TemplateRoute($target, new MethodMap($server));
            }

            // StaticRoute
            return new StaticRoute($target, new MethodMap($server));
        }

        // Regex
        return new RegexRoute($target, new MethodMap($server));
    }
}
