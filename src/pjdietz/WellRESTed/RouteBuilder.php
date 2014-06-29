<?php

/**
 * pjdietz\WellRESTed\RouteBuilder
 *
 * @author PJ Dietz <pj@pjdietz.com>
 * @copyright Copyright 2014 by PJ Dietz
 * @license MIT
 */

namespace pjdietz\WellRESTed;

use pjdietz\WellRESTed\Exceptions\ParseException;
use pjdietz\WellRESTed\Interfaces\HandlerInterface;
use pjdietz\WellRESTed\Routes\RegexRoute;
use pjdietz\WellRESTed\Routes\StaticRoute;
use pjdietz\WellRESTed\Routes\TemplateRoute;
use stdClass;

/**
 * Class for facilitating constructing Routers.
 */
class RouteBuilder
{
    private $handlerNamespace;
    private $templateVariablePatterns;
    private $defaultVariablePattern;

    /**
     * Contruct and return an array of routes.
     *
     * @param $data
     * @return array
     * @throws Exceptions\ParseException
     */
    public function buildRoutes($data)
    {
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

    public function buildRoutesFromJson($json) {
        return $this->buildRoutes(json_decode($json));
    }

    /**
     * @param stdClass|array $item
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

        // Regex Rout
        if (isset($item->pattern)) {
            return new RegexRoute($item->pattern, $handler);
        }

        return null;
    }

    protected function readConfiguration($data)
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

    protected function getTemplateVariablePattern($variable) {
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

    /**
     * @param mixed $handlerNamespace
     */
    public function setHandlerNamespace($handlerNamespace)
    {
        $this->handlerNamespace = $handlerNamespace;
    }

    /**
     * @return mixed
     */
    public function getHandlerNamespace()
    {
        return $this->handlerNamespace;
    }

    /**
     * @param array $vars
     * @internal param mixed $templateVars
     */
    public function setTemplateVars(array $vars)
    {
        foreach ($vars as $name => $var) {
            $vars[$name] = $this->getTemplateVariablePattern($var);
        }
        $this->templateVariablePatterns = $vars;
    }

    /**
     * @return mixed
     */
    public function getTemplateVars()
    {
        return $this->templateVariablePatterns;
    }

    /**
     * @param mixed $defaultVariablePattern
     */
    public function setDefaultVariablePattern($defaultVariablePattern)
    {
        $this->defaultVariablePattern = $this->getTemplateVariablePattern($defaultVariablePattern);
    }

    /**
     * @return mixed
     */
    public function getDefaultVariablePattern()
    {
        return $this->defaultVariablePattern;
    }

}
