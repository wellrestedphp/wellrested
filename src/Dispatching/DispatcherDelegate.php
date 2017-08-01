<?php

namespace WellRESTed\Dispatching;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\ServerMiddleware\DelegateInterface;

/**
 * Adapter to allow use of PSR-15 Middleware with double pass implementations.
 */
class DispatcherDelegate implements DelegateInterface
{
    /** @var ResponseInterface */
    private $response;
    /** @var callable */
    private $next;

    public function __construct(ResponseInterface $response, callable $next)
    {
        $this->response = $response;
        $this->next = $next;
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        return call_user_func($this->next, $request, $this->response);
    }
}
