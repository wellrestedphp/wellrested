<?php

namespace pjdietz\WellRESTed\exceptions;

require_once(dirname(__FILE__) . '/WellrestedException.php');

/**
 * Exception related to a cURL operation. The message and code should correspond
 * to the cURL error and error number that caused the excpetion.
 *
 */
class CurlException extends WellrestedException {}
