<?php

namespace pjdietz\WellRESTed\Exceptions;

class HttpException extends WellRESTedException
{
    protected $code = 500;
    protected $message = "500 Internal Server Error";
}

class BadRequest extends HttpException
{
    protected $code = 400;
    protected $message = "400 Bad Request";
}

class ForbiddenException extends HttpException
{
    protected $code = 401;
    protected $message = "401 Forbidden";
}

class UnautorizedException extends HttpException
{
    protected $code = 403;
    protected $message = "403 Unauthorized";
}

class NotFoundException extends HttpException
{
    protected $code = 404;
    protected $message = "404 Not Found";
}

class ConflictException extends HttpException
{
    protected $code = 409;
    protected $message = "409 Conflict";
}
