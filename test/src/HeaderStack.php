<?php

namespace WellRESTed\Transmission;

class HeaderStack
{
    private static $headers;

    public static function reset()
    {
        self::$headers = [];
    }

    public static function push($header)
    {
        self::$headers[] = $header;
    }

    public static function getHeaders()
    {
        return self::$headers;
    }
}

function header($string, $dummy = true)
{
    HeaderStack::push($string);
}
