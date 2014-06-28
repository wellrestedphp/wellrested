<?php

namespace pjdietz\WellRESTed\Interfaces;

/**
 * Provides a mechanism for obtaining a response given a request.
 * @package pjdietz\WellRESTed\Interfaces
 */
interface DispatcherInterface {

    /**
     * @param RoutableInterface $request The request to build a responce for.
     * @return ResponseInterface|null
     */
    public function getResponse(RoutableInterface $request);

}
