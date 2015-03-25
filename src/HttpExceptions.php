<?php

/**
 * HttpException and its subclasses provides exceptions that correspond to HTTP
 * error status codes. The most common are included, but you may create
 * additional subclasses if needed by subclassing HttpException.
 *
 * The HttpException classes are intended to be used with Routers or Handler
 * subclasses (pjdietz\WellRESTed\Handler). These classes will catch
 * HttpExceptions and convert them to responses using the exception's code as
 * the HTTP status code and the exception's message as the response body.
 *
 * @author PJ Dietz <pj@pjdietz.com>
 * @copyright Copyright 2015 by PJ Dietz
 * @license MIT
 */

namespace WellRESTed\HttpExceptions;

use Exception;

/**
 * Base exception for HTTP-related errors. Also represents a 500 Internal Server error.
 */
class HttpException extends Exception
{
    /** @var int HTTP Status Code */
    protected $code = 500;
    /** @var string Default description for the error */
    protected $message = "500 Internal Server Error";
}

/**
 * Represents a 400 Bad Request error.
 */
class BadRequestException extends HttpException
{
    /** @var int HTTP Status Code */
    protected $code = 400;
    /** @var string Default description for the error */
    protected $message = "400 Bad Request";
}

/**
 * Represents a 401 Unauthorization error.
 */
class UnauthorizedException extends HttpException
{
    /** @var int HTTP Status Code */
    protected $code = 401;
    /** @var string Default description for the error */
    protected $message = "401 Unauthorized";
}

/**
 * Represents a 403 Forbidden error.
 */
class ForbiddenException extends HttpException
{
    /** @var int HTTP Status Code */
    protected $code = 403;
    /** @var string Default description for the error */
    protected $message = "403 Forbidden";
}

/**
 * Represents a 404 Not Found error.
 */
class NotFoundException extends HttpException
{
    /** @var int HTTP Status Code */
    protected $code = 404;
    /** @var string Default description for the error */
    protected $message = "404 Not Found";
}

/**
 * Represents a 405 Method Not Allowed error.
 */
class MethodNotAllowedException extends HttpException
{
    /** @var int HTTP Status Code */
    protected $code = 405;
    /** @var string Default description for the error */
    protected $message = "405 Method Not Allowed";
}

/**
 * Represents a 409 Conflict error.
 */
class ConflictException extends HttpException
{
    /** @var int HTTP Status Code */
    protected $code = 409;
    /** @var string Default description for the error */
    protected $message = "409 Conflict";
}

/**
 * Represents a 410 Gone error.
 */
class GoneException extends HttpException
{
    /** @var int HTTP Status Code */
    protected $code = 410;
    /** @var string Default description for the error */
    protected $message = "410 Gone";
}
