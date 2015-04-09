<?php

/**
 * Add a dummy apache_request_headers method in the global namesapce.
 */

if (!function_exists("apache_request_headers")) {
    function apache_request_headers() {
        return [];
    }
}
