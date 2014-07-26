<?php

namespace pjdietz\WellRESTed\Test;

use Faker\Factory;
use pjdietz\WellRESTed\Response;

class ResponseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider statusCodeProvider
     */
    public function testSetStatusCodeInConstructor($statusCode, $reasonPhrase, $statusLine)
    {
        $resp = new Response($statusCode);
        $this->assertEquals($statusCode, $resp->getStatusCode());
    }

    /**
     * @dataProvider statusCodeProvider
     */
    public function testReadStatusLine($statusCode, $reasonPhrase, $statusLine)
    {
        $resp = new Response();
        $resp->setStatusCode($statusCode, $reasonPhrase);
        $this->assertEquals($statusLine, $resp->getStatusLine());
    }

    /**
     * @dataProvider statusCodeProvider
     */
    public function testReadSuccess($statusCode, $reasonPhrase, $statusLine)
    {
        $resp = new Response();
        $resp->setStatusCode($statusCode, $reasonPhrase);
        if ($statusCode < 400) {
            $this->assertTrue($resp->getSuccess());
        } else {
            $this->assertFalse($resp->getSuccess());
        }
    }

    /**
     * @dataProvider statusCodeProvider
     */
    public function testReadReasonPhrase($statusCode, $reasonPhrase, $statusLine)
    {
        $resp = new Response();
        $resp->setStatusCode($statusCode, $reasonPhrase);
        $this->assertEquals(substr($statusLine, 13), $resp->getReasonPhrase());
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
    public function testFailOnInvalidReasonPhrase($statusCode, $reasonPhrase)
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

    public function testSetBody()
    {
        $faker = Factory::create();
        $body = $faker->text();
        $resp = new Response();
        $resp->setBody($body);
        $this->assertEquals($body, $resp->getBody());
    }

    public function testSetBodyInConstructor()
    {
        $faker = Factory::create();
        $body = $faker->text();
        $resp = new Response(200, $body);
        $this->assertEquals($body, $resp->getBody());
    }

    public function testSetBodyFile()
    {
        $path = tempnam(sys_get_temp_dir(), "TST");
        $resp = new Response();
        $resp->setBodyFilePath($path);
        $this->assertEquals($path, $resp->getBodyFilePath());
        unlink($path);
    }

    /**
     * @dataProvider headerProvider
     */
    public function testSetHeaders($headerKey, $headerValue, $testName)
    {
        $resp = new Response();
        $resp->setHeader($headerKey, $headerValue);
        $this->assertEquals($headerValue, $resp->getHeader($testName));
    }

    /**
     * @dataProvider headerProvider
     */
    public function testSetHeadersInConstructor($headerKey, $headerValue, $testName)
    {
        $resp = new Response(200, "Body", array($headerKey => $headerValue));
        $this->assertEquals($headerValue, $resp->getHeader($testName));
    }

    public function headerProvider()
    {
        return [
            ["Content-Encoding", "gzip", "CONTENT-ENCODING"],
            ["Content-Length", "2048", "content-length"],
            ["Content-Type", "text/plain", "Content-Type"]
        ];
    }

    public function testOutputResponse()
    {
        $faker = Factory::create();
        $body = $faker->text();

        $resp = new Response(200, $body, ["Content-type" => "text/plain"]);
        ob_start();
        @$resp->respond();
        $captured = ob_get_contents();
        ob_end_clean();

        $this->assertEquals($body, $captured);
    }

    public function testOutputResponseFromFile()
    {
        $path = tempnam(sys_get_temp_dir(), "TST");
        $faker = Factory::create();
        $body = $faker->text();

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

    public function testMissingResponseFile()
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

}
