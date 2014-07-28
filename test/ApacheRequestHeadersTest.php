<?php

// This file must be in the global namespace to add apache_request_headers

use pjdietz\WellRESTed\Request;

class ApacheRequestHeadersTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testReadApacheRequestHeaders()
    {
        if (!function_exists('apache_request_headers')) {
            function apache_request_headers() {
                return array();
            }
        }

        $headers = Request::getRequestHeaders();
        $this->assertNotNull($headers);
    }
}
