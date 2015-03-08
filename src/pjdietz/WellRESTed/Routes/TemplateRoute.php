<?php

/**
 * pjdietz\WellRESTed\TemplateRoute
 *
 * @author PJ Dietz <pj@pjdietz.com>
 * @copyright Copyright 2015 by PJ Dietz
 * @license MIT
 */

namespace pjdietz\WellRESTed\Routes;

/**
 * Maps a URI template to a Handler
 */
class TemplateRoute extends RegexRoute
{
    /**
     * Regular expression matching URL friendly characters (i.e., letters,
     * digits, hyphen and underscore)
     */
    const RE_SLUG = '[0-9a-zA-Z\-_]+';
    /** Regular expression matching digitis */
    const RE_NUM = '[0-9]+';
    /** Regular expression matching letters */
    const RE_ALPHA = '[a-zA-Z]+';
    /** Regular expression matching letters and digits */
    const RE_ALPHANUM = '[0-9a-zA-Z]+';
    /** Regular expression matching a URI template variable (e.g., {id}) */
    const URI_TEMPLATE_EXPRESSION_RE = '/{([[a-zA-Z][a-zA-Z0-_]*)}/';

    /**
     * Create a new route that matches a URI template to a handler.
     *
     * Optionally provide patterns for the variables in the template.
     *
     * @param string $template URI template the path must match
     * @param mixed $target Handler to dispatch
     * @param string $defaultPattern Regular expression for variables
     * @param array $variablePatterns Map of variable names and partial regular expression
     *
     * @see BaseRoute for details about $target
     */
    public function __construct(
        $template,
        $target,
        $defaultPattern = self::RE_SLUG,
        $variablePatterns = null
    ) {
        $pattern = $this->buildPattern($template, $defaultPattern, $variablePatterns);
        parent::__construct($pattern, $target);
    }

    /**
     * Translate the URI template into a regular expression.
     *
     * @param string $template URI template the path must match
     * @param string $defaultPattern Regular expression for variables
     * @param array $variablePatterns Map of variable names and regular expression
     * @return string
     */
    private function buildPattern($template, $defaultPattern, $variablePatterns)
    {
        // Ensure $variablePatterns is an array.
        if (is_null($variablePatterns)) {
            $variablePatterns = array();
        } elseif (is_object($variablePatterns)) {
            $variablePatterns = (array) $variablePatterns;
        }

        // Ensure a default is set.
        if (!$defaultPattern) {
            $defaultPattern = self::RE_SLUG;
        }

        // Convert the template into the pattern
        $pattern = $template;

        // Escape allowable characters with regex meaning.
        $pattern = str_replace(
            array("-", "."),
            array("\\-", "\\."),
            $pattern);

        // Replace * with .* AFTER escaping to avoid escaping .*
        $pattern = str_replace("*", ".*", $pattern);

        // Surround the pattern with delimiters.
        $pattern = "~^{$pattern}$~";

        // Replace all template variables with matching subpatterns.
        $callback = function ($matches) use ($variablePatterns, $defaultPattern) {
            $key = $matches[1];
            if (isset($variablePatterns[$key])) {
                $pattern = $variablePatterns[$key];
            } else {
                $pattern = $defaultPattern;
            }
            return "(?<{$key}>{$pattern})";
        };
        $pattern = preg_replace_callback(self::URI_TEMPLATE_EXPRESSION_RE, $callback, $pattern);

        return $pattern;
    }
}
