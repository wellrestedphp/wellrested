<?php

declare(strict_types=1);

namespace WellRESTed\Dispatching;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WellRESTed\MiddlewareInterface;
use WellRESTed\Server;
use WellRESTed\ServerReferenceTrait;

/**
 * Ordered sequence of middleware and handlers.
 */
class DispatchQueue implements MiddlewareInterface
{
    use ServerReferenceTrait;

    /** @var mixed[] */
    private array $dispatchables;

    public function __construct(Server $server, array $dispatchables = [])
    {
        $this->setServer($server);
        $this->dispatchables = $dispatchables;
    }

    /**
     * Push a new middleware onto the stack.
     *
     * @param mixed $dispatchable Middleware to dispatch in sequence
     * @return static
     */
    public function add($dispatchable)
    {
        $this->dispatchables[] = $dispatchable;
        return $this;
    }

    /**
     * Dispatch the contained middleware in the order in which they were added.
     *
     * The first middleware that was added is dispatched first.
     *
     * Each middleware, when dispatched, receives a $next callable that, when
     * called, will dispatch the following middleware in the sequence.
     *
     * When any dispatchale in the queue returns a response without calling its
     * $next, the queue will not call the $next it received.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable $next
     * @return ResponseInterface
     */
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        $next
    ) {
        $dispatcher = $this->getServer()->getDispatcher();

        // This flag will be set to true when the last middleware calls $next.
        $stackCompleted = false;

        // The final middleware's $next returns $response unchanged and sets
        // the $stackCompleted flag to indicate the stack has completed.
        $chain = function (
            ServerRequestInterface $request,
            ResponseInterface $response
        ) use (&$stackCompleted): ResponseInterface {
            $stackCompleted = true;
            return $response;
        };

        // Create a chain of callables.
        //
        // Each callable will take $request and $response parameters, and will
        // contain a dispatcher, the associated middleware, and a $next function
        // that serves as the link to the next middleware in the chain.
        foreach (array_reverse($this->dispatchables) as $middleware) {
            $chain = function (
                ServerRequestInterface $request,
                ResponseInterface $response
            ) use ($dispatcher, $middleware, $chain): ResponseInterface {
                return $dispatcher->dispatch(
                    $middleware,
                    $request,
                    $response,
                    $chain
                );
            };
        }

        $response = $chain($request, $response);

        if ($stackCompleted) {
            return $next($request, $response);
        } else {
            return $response;
        }
    }
}
