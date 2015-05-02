<?php

namespace WellRESTed\Message;

class UploadedFileState
{
    public static $php_sapi_name;
    public static $is_uploaded_file;
}

function php_sapi_name()
{
    return UploadedFileState::$php_sapi_name;
}

function move_uploaded_file($source, $target)
{
    return rename($source, $target);
}

function is_uploaded_file($file)
{
    return UploadedFileState::$is_uploaded_file;
}
