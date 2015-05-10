<?php

namespace WellRESTed\Dispatching;

interface DispatchProviderInterface
{
    /**
     * @return DispatcherInterface
     */
    public function getDispatcher();

    /**
     * Return a DispatchStackInterface for a list array of middleware.
     *
     * @param mixed[] $middlewares
     * @return DispatchStackInterface
     */
    public function getDispatchStack($middlewares);
}
