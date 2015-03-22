<?php

namespace WellRESTed\Test\Unit\Message;

use WellRESTed\Message\Response;

class ResponseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers WellRESTed\Message\Response::withStatus
     * @covers WellRESTed\Message\Response::getStatusCode
     * @uses   WellRESTed\Message\Response::__clone
     * @uses   WellRESTed\Message\Message
     * @uses   WellRESTed\Message\HeaderCollection
     */
    public function testCreatesNewInstanceWithStatusCode()
    {
        $response = new Response();
        $copy = $response->withStatus(200);
        $this->assertEquals(200, $copy->getStatusCode());
    }

    /**
     * @covers WellRESTed\Message\Response::withStatus
     * @covers WellRESTed\Message\Response::getReasonPhrase
     * @uses   WellRESTed\Message\Response::__clone
     * @uses   WellRESTed\Message\Message
     * @uses   WellRESTed\Message\HeaderCollection
     * @dataProvider statusProvider
     */
    public function testCreatesNewInstanceWithReasonPhrase($code, $reasonPhrase, $expected)
    {
        $response = new Response();
        $copy = $response->withStatus($code, $reasonPhrase);
        $this->assertEquals($expected, $copy->getReasonPhrase());
    }

    public function statusProvider()
    {
        return [
            [100, null, "Continue"],
            [101, null, "Switching Protocols"],
            [200, null, "OK"],
            [201, null, "Created"],
            [202, null, "Accepted"],
            [203, null, "Non-Authoritative Information"],
            [204, null, "No Content"],
            [205, null, "Reset Content"],
            [206, null, "Partial Content"],
            [300, null, "Multiple Choices"],
            [301, null, "Moved Permanently"],
            [302, null, "Found"],
            [303, null, "See Other"],
            [304, null, "Not Modified"],
            [305, null, "Use Proxy"],
            [400, null, "Bad Request"],
            [401, null, "Unauthorized"],
            [402, null, "Payment Required"],
            [403, null, "Forbidden"],
            [404, null, "Not Found"],
            [405, null, "Method Not Allowed"],
            [406, null, "Not Acceptable"],
            [407, null, "Proxy Authentication Required"],
            [408, null, "Request Timeout"],
            [409, null, "Conflict"],
            [410, null, "Gone"],
            [411, null, "Length Required"],
            [412, null, "Precondition Failed"],
            [413, null, "Payload Too Large"],
            [414, null, "URI Too Long"],
            [415, null, "Unsupported Media Type"],
            [500, null, "Internal Server Error"],
            [501, null, "Not Implemented"],
            [502, null, "Bad Gateway"],
            [503, null, "Service Unavailable"],
            [504, null, "Gateway Timeout"],
            [505, null, "HTTP Version Not Supported"],
            [598, null, "Unknown"],
            [599, "Nonstandard", "Nonstandard"]
        ];
    }

    /**
     * @covers WellRESTed\Message\Response::withStatus
     * @covers WellRESTed\Message\Response::getStatusCode
     * @uses   WellRESTed\Message\Response::__clone
     * @uses   WellRESTed\Message\Message
     * @uses   WellRESTed\Message\HeaderCollection
     */
    public function testWithStatusCodePreservesOriginalResponse()
    {
        $response1 = new Response();
        $response1 = $response1->withStatus(200);
        $response1 = $response1->withHeader("Content-type", "application/json");

        $response2 = $response1->withStatus(404);
        $response2 = $response2->withHeader("Content-type", "text/plain");


        $this->assertEquals(200, $response1->getStatusCode());
        $this->assertEquals("application/json", $response1->getHeader("Content-type"));

        $this->assertEquals(404, $response2->getStatusCode());
        $this->assertEquals("text/plain", $response2->getHeader("Content-type"));
    }
}
