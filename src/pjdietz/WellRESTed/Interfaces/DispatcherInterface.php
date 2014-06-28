<?php

namespace pjdietz\WellRESTed\Interfaces;

/**
 * Provides a mechanism for obtaining a response given a request.
 * @package pjdietz\WellRESTed\Interfaces
 */
interface DispatcherInterface {

    /**
     * @param RoutableInterface $request The request to build a responce for.
     * @param array|null $args Optional associate array of arguments.
     * @return ResponseInterface|null
     */
    public function getResponse(RoutableInterface $request, $args = null);

}
