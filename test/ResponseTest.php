<?php

use pjdietz\WellRESTed\Request;
use pjdietz\WellRESTed\Response;
use pjdietz\WellRESTed\Test;

class ResponseBuilderTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        ob_start();
    }

    public function tearDown()
    {
        ob_clean();
        ob_end_clean();
    }

    public function testConstructor()
    {
        $resp = new Response(200, "This is the body", array("Content-type" => "text/plain"));
    }

    /**
     * @dataProvider statusCodeProvider
     */
    public function testStatusLine($statusCode, $reasonPhrase, $statusLine)
    {
        $resp = new Response();
        $resp->setStatusCode($statusCode, $reasonPhrase);
        $this->assertEquals($statusLine, $resp->getStatusLine());
    }

    /**
     * @dataProvider statusCodeProvider
     */
    public function testReasonPhrase($statusCode, $reasonPhrase, $statusLine)
    {
        $resp = new Response();
        $resp->setStatusCode($statusCode, $reasonPhrase);
        $this->assertEquals(substr($statusLine, 13), $resp->getReasonPhrase());
    }

    /**
     * @dataProvider statusCodeProvider
     */
    public function testSuccess($statusCode, $reasonPhrase, $statusLine)
    {
        $resp = new Response();
        $resp->setStatusCode($statusCode, $reasonPhrase);
        if ($statusCode < 400) {
            $this->assertTrue($resp->getSuccess());
        } else {
            $this->assertFalse($resp->getSuccess());
        }
    }

    public function statusCodeProvider()
    {
        return [
            [100, null, "HTTP/1.1 100 Continue"],
            [101, null, "HTTP/1.1 101 Switching Protocols"],
            [200, null, "HTTP/1.1 200 OK"],
            [201, null, "HTTP/1.1 201 Created"],
            [202, null, "HTTP/1.1 202 Accepted"],
            [203, null, "HTTP/1.1 203 Non-Authoritative Information"],
            [204, null, "HTTP/1.1 204 No Content"],
            [205, null, "HTTP/1.1 205 Reset Content"],
            [206, null, "HTTP/1.1 206 Partial Content"],
            [300, null, "HTTP/1.1 300 Multiple Choices"],
            [301, null, "HTTP/1.1 301 Moved Permanently"],
            [302, null, "HTTP/1.1 302 Found"],
            [303, null, "HTTP/1.1 303 See Other"],
            [304, null, "HTTP/1.1 304 Not Modified"],
            [305, null, "HTTP/1.1 305 Use Proxy"],
            [400, null, "HTTP/1.1 400 Bad Request"],
            [401, null, "HTTP/1.1 401 Unauthorized"],
            [402, null, "HTTP/1.1 402 Payment Required"],
            [403, null, "HTTP/1.1 403 Forbidden"],
            [404, null, "HTTP/1.1 404 Not Found"],
            [405, null, "HTTP/1.1 405 Method Not Allowed"],
            [406, null, "HTTP/1.1 406 Not Acceptable"],
            [407, null, "HTTP/1.1 407 Proxy Authentication Required"],
            [408, null, "HTTP/1.1 408 Request Timeout"],
            [409, null, "HTTP/1.1 409 Conflict"],
            [410, null, "HTTP/1.1 410 Gone"],
            [411, null, "HTTP/1.1 411 Length Required"],
            [412, null, "HTTP/1.1 412 Precondition Failed"],
            [413, null, "HTTP/1.1 413 Request Entity Too Large"],
            [414, null, "HTTP/1.1 414 Request-URI Too Long"],
            [415, null, "HTTP/1.1 415 Unsupported Media Type"],
            [500, null, "HTTP/1.1 500 Internal Server Error"],
            [501, null, "HTTP/1.1 501 Not Implemented"],
            [502, null, "HTTP/1.1 502 Bad Gateway"],
            [503, null, "HTTP/1.1 503 Service Unavailable"],
            [504, null, "HTTP/1.1 504 Gateway Timeout"],
            [505, null, "HTTP/1.1 505 HTTP Version Not Supported"],
            [598, null, "HTTP/1.1 598 Nonstandard"],
            [599, "Smelly", "HTTP/1.1 599 Smelly"]
        ];
    }

    /**
     * @dataProvider invalidReasonPhraseProvider
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidReasonPhrase($statusCode, $reasonPhrase)
    {
        $resp = new Response();
        $resp->setStatusCode($statusCode, $reasonPhrase);
    }

    public function invalidReasonPhraseProvider()
    {
        return [
            [599, false],
            ["100", true],
            ["*", []]
        ];
    }

    public function testBodyFile()
    {
        $path = tempnam(sys_get_temp_dir(), "TST");
        $resp = new Response();
        $resp->setBodyFilePath($path);
        $this->assertEquals($path, $resp->getBodyFilePath());
        unlink($path);
    }

    public function testRespondBodyFile()
    {
        $path = tempnam(sys_get_temp_dir(), "TST");
        $body = "This is the body";

        $f = fopen($path, "w");
        fwrite($f, $body);
        fclose($f);

        $resp = new Response();
        $resp->setStatusCode(200);
        $resp->setBodyFilePath($path);

        ob_start();
        ob_clean();
        @$resp->respond();
        $captured = ob_get_contents();
        ob_end_clean();

        unlink($path);

        $this->assertEquals($captured, $body);
    }

    public function testMissingRespondBodyFile()
    {
        $path = tempnam(sys_get_temp_dir(), "TST");

        $resp = new Response();
        $resp->setStatusCode(200);
        $resp->setBodyFilePath($path);
        unlink($path);

        ob_start();
        ob_clean();
        @$resp->respond();
        $captured = ob_get_contents();
        ob_end_clean();

        $this->assertEquals("", $captured);
    }

    public function testRespondBody()
    {
        $body = "This is the body";

        $resp = new Response(200, $body, array("Content-type" => "text/plain"));
        ob_start();
        @$resp->respond();
        $captured = ob_get_contents();
        ob_end_clean();

        $this->assertEquals($body, $captured);
    }



}
