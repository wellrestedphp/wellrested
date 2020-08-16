<?php

namespace WellRESTed\Message;

// Declare functions in this namespace so the class under test will use these
// instead of the internal global functions during testing.

class StreamHelper
{
    public static $fail = false;
}

function fseek($resource, $offset, $whence = SEEK_SET)
{
    if (StreamHelper::$fail) {
        return -1;
    }
    return \fseek($resource, $offset, $whence);
}

function ftell($resource)
{
    if (StreamHelper::$fail) {
        return false;
    }
    return \ftell($resource);
}

function rewind($resource)
{
    if (StreamHelper::$fail) {
        return false;
    }
    return \rewind($resource);
}