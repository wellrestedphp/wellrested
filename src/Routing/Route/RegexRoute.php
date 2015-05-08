<?php

namespace WellRESTed\Routing\Route;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class RegexRoute extends Route
{
    private $captures;

    public function getType()
    {
        return RouteInterface::TYPE_PATTERN;
    }

    /**
     * @param string $requestTarget
     * @return bool
     * @throws \RuntimeException
     */
    public function matchesRequestTarget($requestTarget)
    {
        $matched = @preg_match($this->getTarget(), $requestTarget, $captures);
        if ($matched) {
            $this->captures = $captures;
            return true;
        } elseif ($matched === false) {
            throw new \RuntimeException("Invalid regular expression: " . $this->getTarget());
        }
        return false;
    }

    public function dispatch(ServerRequestInterface $request, ResponseInterface &$response)
    {
        if ($this->captures) {
            $request = $request->withAttribute("path", $this->captures);
        }
        parent::dispatch($request, $response);
    }
}
