<?php

namespace pjdietz\WellRESTed\Exceptions;

/**
 * Exception related to a cURL operation. The message and code should correspond
 * to the cURL error and error number that caused the excpetion.
 *
 */
class CurlException extends WellrestedException {}
