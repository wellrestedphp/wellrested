<?php

namespace WellRESTed\Stream;

class StringStream extends Stream
{
    public function __construct($string = "")
    {
        $handle = fopen("php://memory", "w+");
        fwrite($handle, $string);
        parent::__construct($handle);
    }
}
