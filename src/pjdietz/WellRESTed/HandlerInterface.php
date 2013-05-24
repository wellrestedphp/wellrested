<?php

namespace pjdietz\WellRESTed;

/**
 * Interface for a creating a response in reaction to a respond or arguments.
 * @package pjdietz\WellRESTed
 */
interface HandlerInterface
{
    public function getRequest();
    public function setRequest(RequestInterface $request);
    public function getArguments();
    public function setArguments(array $args);
    public function getResponse();
}
