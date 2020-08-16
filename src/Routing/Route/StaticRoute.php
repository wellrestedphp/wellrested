<?php

namespace WellRESTed\Routing\Route;

/**
 * @internal
 */
class StaticRoute extends Route
{
    public function getType(): int
    {
        return Route::TYPE_STATIC;
    }

    /**
     * Examines a request target to see if it is a match for the route.
     *
     * @param string $requestTarget
     * @return bool
     */
    public function matchesRequestTarget(string $requestTarget): bool
    {
        return $requestTarget === $this->getTarget();
    }

    /**
     * Always returns an empty array.
     */
    public function getPathVariables(): array
    {
        return [];
    }
}
