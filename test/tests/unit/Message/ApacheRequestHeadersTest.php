<?php

// This file must be in the global namespace to add apache_request_headers

use WellRESTed\Message\ServerRequest;

class ApacheRequestHeadersTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers WellRESTed\Message\ServerRequest::getServerRequestHeaders
     * @uses WellRESTed\Message\ServerRequest::getServerRequest
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testReadsApacheRequestHeaders()
    {
        if (!function_exists("apache_request_headers")) {
            function apache_request_headers() {
                return [];
            }
        }

        $_SERVER = [
            "HTTP_HOST" => "localhost",
            "HTTP_ACCEPT" => "application/json",
            "QUERY_STRING" => "guinea_pig=Claude&hamster=Fizzgig"
        ];
        $_COOKIE = [
            "cat" => "Molly"
        ];
        $_FILES = [
            "file" => [
                "name" => "MyFile.jpg",
                "type" => "image/jpeg",
                "tmp_name" => "/tmp/php/php6hst32",
                "error" => "UPLOAD_ERR_OK",
                "size" => 98174
            ]
        ];
        $_POST = [
            "dog" => "Bear"
        ];

        $request = ServerRequest::getServerRequest();
        $headers = $request->getHeaders();
        $this->assertNotNull($headers);
    }
}
