<?php

namespace WellRESTed\Routing\Route;

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
     * @param string|array $variablePattern Regular expression for variables
     *
     * @see BaseRoute for details about $target
     */
    public function __construct(
        $template,
        $middleware,
        $variablePattern = self::RE_SLUG
    ) {
        $pattern = $this->buildPattern($template, $variablePattern);
        parent::__construct($pattern, $middleware);
    }

    /**
     * Translate the URI template into a regular expression.
     *
     * @param string $template URI template the path must match
     * @param string|array $variablePattern Regular expression for variables
     * @return string
     */
    private function buildPattern($template, $variablePattern)
    {
        $defaultPattern = self::RE_SLUG;
        $variablePatterns = [];

        if (is_string($variablePattern)) {
            $defaultPattern = $variablePattern;
        } elseif (is_array($variablePattern)) {
            $variablePatterns = $variablePattern;
            if (isset($variablePatterns["*"])) {
                $defaultPattern = $variablePatterns["*"];
            }
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
