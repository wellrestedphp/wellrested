<?php

namespace WellRESTed\Routing\Route;

class TemplateRoute extends Route
{
    private $pathVariables;
    private $explosions;

    /**
     * Regular expression matching 1 or more unreserved characters.
     * ALPHA / DIGIT / "-" / "." / "_" / "~"
     */
    const RE_UNRESERVED = '[0-9a-zA-Z\-._\~%]*';
    /** Regular expression matching a URI template variable (e.g., {id}) */
    const URI_TEMPLATE_EXPRESSION_RE = '/{([+.\/]?[a-zA-Z0-9_,]+\*?)}/';

    public function getType()
    {
        return RouteInterface::TYPE_PATTERN;
    }

    public function getPathVariables()
    {
        return $this->pathVariables ?: [];
    }

    /**
     * Examines a request target to see if it is a match for the route.
     *
     * @param string $requestTarget
     * @return bool
     */
    public function matchesRequestTarget($requestTarget)
    {
        $this->pathVariables = [];
        $this->explosions = [];

        if (!$this->matchesStartOfRequestTarget($requestTarget)) {
            return false;
        }

        $matchingPattern = $this->getMatchingPattern();

        if (preg_match($matchingPattern, $requestTarget, $captures)) {
            $this->pathVariables = $this->processMatches($captures);
            return true;
        }
        return false;
    }

    /**
     * @param $requestTarget
     * @return bool
     */
    private function matchesStartOfRequestTarget($requestTarget)
    {
        $firstVarPos = strpos($this->target, "{");
        if ($firstVarPos === false) {
            return $requestTarget === $this->target;
        }
        return (substr($requestTarget, 0, $firstVarPos) === substr($this->target, 0, $firstVarPos));
    }

    private function processMatches($matches)
    {
        $variables = [];

        // Isolate the named captures.
        $keys = array_filter(array_keys($matches), "is_string");

        // Store named captures to the variables.
        foreach ($keys as $key) {

            $value = $matches[$key];

            if (isset($this->explosions[$key])) {
                $values = explode($this->explosions[$key], $value);
                $variables[$key] = array_map("urldecode", $values);
            } else {
                $value = urldecode($value);
                $variables[$key] = $value;
            }

        }

        return $variables;
    }

    private function getMatchingPattern()
    {
        // Convert the template into the pattern
        $pattern = $this->target;

        // Escape allowable characters with regex meaning.
        $escape = [
            "." => "\\.",
            "-" => "\\-",
            "+" => "\\+",
            "*" => "\\*"
        ];
        $pattern = str_replace(array_keys($escape), array_values($escape), $pattern);
        $unescape = [
            "{\\+" => "{+",
            "{\\." => "{.",
            "\\*}" => "*}"
        ];
        $pattern = str_replace(array_keys($unescape), array_values($unescape), $pattern);

        // Surround the pattern with delimiters.
        $pattern = "~^{$pattern}$~";

        $pattern = preg_replace_callback(
            self::URI_TEMPLATE_EXPRESSION_RE,
            [$this, "uriVariableReplacementCallback"],
            $pattern
        );

        return $pattern;
    }

    private function uriVariableReplacementCallback($matches)
    {
        $name = $matches[1];
        $pattern = self::RE_UNRESERVED;

        $prefix = "";
        $delimiter = ",";
        $explodeDelimiter = ",";

        // Read the first character as an operator. This determines which
        // characters to allow in the match.
        $operator = $name[0];

        // Read the last character as the modifier.
        $explosion = (substr($name, -1, 1) === "*");

        switch ($operator) {
            case "+":
                $name = substr($name, 1);
                $pattern = ".*";
                break;
            case ".":
                $name = substr($name, 1);
                $prefix = "\\.";
                $delimiter = "\\.";
                $explodeDelimiter = ".";
                break;
            case "/":
                $name = substr($name, 1);
                $prefix = "\\/";
                $delimiter = "\\/";
                if ($explosion) {
                    $pattern = '[0-9a-zA-Z\-._\~%,\/]*'; // Unreserved + "," and "/"
                    $explodeDelimiter = "/";
                }
                break;
        }

        // Explosion
        if ($explosion) {
            $name = substr($name, 0, -1);
            if ($pattern === self::RE_UNRESERVED) {
                $pattern = '[0-9a-zA-Z\-._\~%,]*'; // Unreserved + ","
            }
            $this->explosions[$name] = $explodeDelimiter;
        }

        $names = explode(",", $name);
        $results = [];
        foreach ($names as $name) {
            $results[] = "(?<{$name}>{$pattern})";
        }
        return $prefix . join($delimiter, $results);
    }
}
