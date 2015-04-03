<?php

namespace WellRESTed\Routing\Route;

class PrefixRoute extends Route implements PrefixRouteInterface
{
    private $prefix;

    public function __construct($prefix, $middleware)
    {
        parent::__construct($middleware);
        $this->prefix = $prefix;
    }

    /**
     * @param string $requestTarget
     * @param array $captures
     * @return bool
     */
    public function matchesRequestTarget($requestTarget, &$captures = null)
    {
        return strrpos($requestTarget, $this->prefix, -strlen($requestTarget)) !== false;
    }

    /**
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }
}
