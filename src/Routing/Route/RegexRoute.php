<?php

namespace WellRESTed\Routing\Route;

class RegexRoute extends Route
{
    private $captures;

    public function getType()
    {
        return RouteInterface::TYPE_PATTERN;
    }

    /**
     * Examines a request target to see if it is a match for the route.
     *
     * @param string $requestTarget
     * @return boolean
     */
    public function matchesRequestTarget($requestTarget)
    {
        $this->captures = [];
        $matched = preg_match($this->getTarget(), $requestTarget, $captures);
        if ($matched) {
            $this->captures = $captures;
            return true;
        } elseif ($matched === false) {
            throw new \RuntimeException("Invalid regular expression: " . $this->getTarget());
        }
        return false;
    }

    /**
     * Returns an array of matches from the last call to matchesRequestTarget.
     *
     * @see \preg_match
     * @return array
     */
    public function getPathVariables()
    {
        return $this->captures;
    }
}
