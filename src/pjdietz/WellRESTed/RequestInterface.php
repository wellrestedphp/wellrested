<?php

namespace pjdietz\WellRESTed;

/**
 * Interface for representing an HTTP request.
 * @package pjdietz\WellRESTed
 */
interface RequestInterface
{
    public function getMethod();
    public function setMethod($method);
    public function getUri();
    public function setUri($uri);
}
