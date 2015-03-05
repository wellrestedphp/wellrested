<?php

/**
 * pjdietz\WellRESTed\RouteBuilder
 *
 * @author PJ Dietz <pj@pjdietz.com>
 * @copyright Copyright 2015 by PJ Dietz
 * @license MIT
 */

namespace pjdietz\WellRESTed;

use pjdietz\WellRESTed\Exceptions\ParseException;
use pjdietz\WellRESTed\Interfaces\HandlerInterface;
use pjdietz\WellRESTed\Routes\RegexRoute;
use pjdietz\WellRESTed\Routes\StaticRoute;
use pjdietz\WellRESTed\Routes\TemplateRoute;

/**
 * Class for facilitating constructing Routers.
 *
 * @deprecated Use {@see Router::add} instead.
 * @see Router::add
 */
class RouteBuilder
{
    /** @var string Regex pattern to use for URI template patters. */
    private $defaultVariablePattern;
    /** @var string Common prefix to affix to handler class names. */
    private $handlerNamespace;
    /** @var array Associative array of variable names and regex patterns. */
    private $templateVariablePatterns;

    /**
     * Create a new RouteBuilder
     *
     * @deprecated Use {@see Router::add} instead.
     * @see Router::add
     */
    public function __construct()
    {
        trigger_error("RouteBuilder is deprecated. Use Router::add", E_USER_DEPRECATED);
    }

    /**
     * Contruct and return an array of routes.
     *
     * If $data is a string, buildRoutes() will parse it as JSON with json_decode.
     *
     * If $data is an array, buildRoutes() assumes each item in the array is
     * an object it can translate into a route.
     *
     * If $data is an object, buildRoutes() assumes it will have a "routes"
     * property with an array value that is a collection of objects to
     * translate into routes. Any other properties will be read with
     * readConfiguration()
     *
     * @param string|array|object $data Description of routes to build.
     * @return array List of routes to add to a router.
     * @throws Exceptions\ParseException
     */
    public function buildRoutes($data)
    {
        // If $data is a string, attempt to parse it as JSON.
        if (is_string($data)) {
            $data = json_decode($data);
            if (is_null($data)) {
                throw new ParseException("Unable to parse as JSON.");
            }
        }

        // Locate the list of routes. This should be one of these:
        // - If $data is an object, $data->routes
        // - If $data is an array, $data
        if (is_array($data)) {
            $dataRoutes = $data;
        } elseif (is_object($data) && isset($data->routes) && is_array($data->routes)) {
            $dataRoutes = $data->routes;
            $this->readConfiguration($data);
        } else {
            throw new ParseException("Unable to parse. Missing array of routes.");
        }

        // Build a route instance and append it to the list.
        $routes = array();
        foreach ($dataRoutes as $item) {
            $routes[] = $this->buildRoute($item);
        }
        return $routes;
    }

    /**
     * Parse an object and update the instances with the new configuration.
     *
     * handlerNamespace is passed to setHandlerNamesapce()

     * variablePattern is passed to setDefaultVariablePattern()
     *
     * vars is passed to setTemplateVars()
     *
     * @param object
     */
    public function readConfiguration($data)
    {
        if (isset($data->handlerNamespace)) {
            $this->setHandlerNamespace($data->handlerNamespace);
        }
        if (isset($data->variablePattern)) {
            $this->setDefaultVariablePattern($data->variablePattern);
        }
        if (isset($data->vars)) {
            $this->setTemplateVars((array) $data->vars);
        }
    }

    /**
     * Return the string to prepend to handler class names.
     *
     * @return string
     */
    public function getHandlerNamespace()
    {
        return $this->handlerNamespace;
    }

    /**
     * Set the prefix to prepend to handler class names.
     *
     * @param mixed $handlerNamespace
     */
    public function setHandlerNamespace($handlerNamespace = "")
    {
        $this->handlerNamespace = $handlerNamespace;
    }

    /**
     * Return an associative array of variable names and regex patterns.
     *
     * @return mixed
     */
    public function getTemplateVars()
    {
        return $this->templateVariablePatterns;
    }

    /**
     * Set the array of template variable patterns.
     *
     * Keys are names of variables for use in URI template (do not include {}).
     * Values are regex patterns or any of the following special names: SLUG,
     * ALPHA, ALPHANUM, DIGIT, NUM.
     *
     * If you wish to use additional named patterns, subclass RouteBuilder and
     * override getTemplateVariablePattern.
     *
     * @param array $vars Associative array of variable name => pattern
     */
    public function setTemplateVars(array $vars)
    {
        foreach ($vars as $name => $var) {
            $vars[$name] = $this->getTemplateVariablePattern($var);
        }
        $this->templateVariablePatterns = $vars;
    }

    /**
     * Return the default regex pattern to use for URI template variables.
     *
     * @return string
     */
    public function getDefaultVariablePattern()
    {
        return $this->defaultVariablePattern;
    }

    /**
     * Set the default regex pattern to use for URI template variables.
     *
     * $defaultVariablePattern may be a regex pattern or one of the following:
     * SLUG, ALPHA, ALPHANUM, DIGIT, NUM.
     *
     * If you wish to use additional named patterns, subclass RouteBuilder and
     * override getTemplateVariablePattern.
     *
     * @param mixed $defaultVariablePattern
     */
    public function setDefaultVariablePattern($defaultVariablePattern)
    {
        $this->defaultVariablePattern = $this->getTemplateVariablePattern($defaultVariablePattern);
    }

    /**
     * Create and return an appropriate route given an object describing a route.
     *
     * $item must contain a "handler" property providing the classname for the
     * HandlerInterface to call getResponse() on if the route matches. "handler"
     * may be fully qualified and begin with "\". If it does not begin with "\",
     * the instance's $handlerNamespace is affixed to the begining.
     *
     * $item must also contain a "path", "template", or "pattern" property to
     * indicate how to create the StaticRoute, TemplateRoute, or RegexRoute.
     *
     * @param object|array $item
     * @return HandlerInterface
     * @throws Exceptions\ParseException
     */
    protected function buildRoute($item)
    {
        // Determine the handler for this route.
        if (isset($item->handler)) {
            $handler = $item->handler;
            if ($handler[0] != "\\") {
                $handler = $this->getHandlerNamespace() . "\\" . $handler;
            }
        } else {
            throw new ParseException("Unable to parse. Route is missing a handler.");
        }

        // Static Route
        if (isset($item->path)) {
            return new StaticRoute($item->path, $handler);
        }

        // Template Route
        if (isset($item->template)) {
            $vars = isset($item->vars) ? (array) $item->vars : array();
            foreach ($vars as $name => $var) {
                $vars[$name] = $this->getTemplateVariablePattern($var);
            }
            if ($this->templateVariablePatterns) {
                $vars = array_merge($this->templateVariablePatterns, $vars);
            }
            return new TemplateRoute($item->template, $handler, $this->getDefaultVariablePattern(), $vars);
        }

        // Regex Route
        if (isset($item->pattern)) {
            return new RegexRoute($item->pattern, $handler);
        }

        return null;
    }

    /**
     * Provide a regular expression pattern given a name.
     *
     * The names SLUG, ALPHA, ALPHANUM, DIGIT, NUM convert to regex patterns.
     * Anything else passes through as is.
     *
     * If you wish to use additional named patterns, subclass RouteBuilder and
     * override getTemplateVariablePattern.
     *
     * @param string $variable Regex pattern or name (SLUG, ALPHA, ALPHANUM, DIGIT, NUM
     * @return string
     */
    protected function getTemplateVariablePattern($variable)
    {
        switch ($variable) {
            case "SLUG":
                return TemplateRoute::RE_SLUG;
            case "ALPHA":
                return TemplateRoute::RE_ALPHA;
            case "ALPHANUM":
                return TemplateRoute::RE_ALPHANUM;
            case "DIGIT":
            case "NUM":
                return TemplateRoute::RE_NUM;
            default:
                return $variable;
        }
    }
}
