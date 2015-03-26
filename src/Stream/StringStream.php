<?php

namespace WellRESTed\Stream;

class StringStream extends StreamStream
{
    public function __construct($string = "")
    {
        $handle = fopen("php://memory", "w+");
        fwrite($handle, $string);
        parent::__construct($handle);
    }
}
