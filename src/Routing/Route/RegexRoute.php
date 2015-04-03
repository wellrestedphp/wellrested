<?php

namespace WellRESTed\Routing\Route;

class RegexRoute extends Route
{
    private $pattern;

    public function __construct($pattern, $middleware)
    {
        parent::__construct($middleware);
        $this->pattern = $pattern;
    }

    /**
     * @param string $requestTarget
     * @param array $captures
     * @return bool
     * @throws \RuntimeException
     */
    public function matchesRequestTarget($requestTarget, &$captures = null)
    {
        $matched = @preg_match($this->pattern, $requestTarget, $captures);
        if ($matched) {
            return true;
        } elseif ($matched === false) {
            throw new \RuntimeException("Invalid regular expression: " . $this->pattern);
        }
        return false;
    }
}
