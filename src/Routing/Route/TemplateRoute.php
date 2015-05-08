<?php

namespace WellRESTed\Routing\Route;

class TemplateRoute extends RegexRoute
{
    /**
     * Regular expression matching 1 or more unreserved characters.
     * ALPHA / DIGIT / "-" / "." / "_" / "~"
     */
    const RE_UNRESERVED = '[0-9a-zA-Z\-._\~]+';
    /** Regular expression matching a URI template variable (e.g., {id}) */
    const URI_TEMPLATE_EXPRESSION_RE = '/{([[a-zA-Z][a-zA-Z0-_]*)}/';

    public function __construct($target, $methodMap)
    {
        $pattern = $this->buildPattern($target);
        parent::__construct($pattern, $methodMap);
    }

    /**
     * Translate the URI template into a regular expression.
     *
     * @param string $template URI template the path must match
     * @return string
     */
    private function buildPattern($template)
    {
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
        $callback = function ($matches) {
            $key = $matches[1];
            // TODO Check for reserved characters, etc.
            $pattern = self::RE_UNRESERVED;
            return "(?<{$key}>{$pattern})";
        };
        $pattern = preg_replace_callback(self::URI_TEMPLATE_EXPRESSION_RE, $callback, $pattern);

        return $pattern;
    }
}
