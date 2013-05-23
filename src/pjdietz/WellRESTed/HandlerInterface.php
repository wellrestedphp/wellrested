<?php

namespace pjdietz\WellRESTed;

interface HandlerInterface
{
    public function getRequest();
    public function setRequest($request);
    public function getArguments();
    public function setArguments(array $args);
    public function getResponse();
}
