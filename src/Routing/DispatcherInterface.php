<?php
/**
 * Created by PhpStorm.
 * User: pjdietz
 * Date: 4/6/15
 * Time: 8:29 PM
 */
namespace WellRESTed\Routing;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface DispatcherInterface
{
    /**
     * @param $middleware
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable $next
     * @return ResponseInterface
     */
    public function dispatch($middleware, ServerRequestInterface $request, ResponseInterface $response, $next);
}
