<?php

namespace WellRESTed\Test\Unit\Message;

use WellRESTed\Message\ServerRequest;

/**
 * @uses WellRESTed\Message\ServerRequest
 * @uses WellRESTed\Message\Request
 * @uses WellRESTed\Message\Message
 * @uses WellRESTed\Message\HeaderCollection
 * @uses WellRESTed\Stream\Stream
 */
class ServerRequestTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers WellRESTed\Message\ServerRequest::__construct
     */
    public function testCreatesInstance()
    {
        $request = new ServerRequest();
        $this->assertNotNull($request);
    }

    /**
     * @covers WellRESTed\Message\ServerRequest::getServerRequest
     * @covers WellRESTed\Message\ServerRequest::getServerRequestHeaders
     * @covers WellRESTed\Message\ServerRequest::readFromServerRequest
     * @covers WellRESTed\Message\ServerRequest::getStreamForBody
     * @preserveGlobalState disabled
     */
    public function testGetServerRequestReadsFromRequest()
    {
        $_SERVER = [
            "HTTP_HOST" => "localhost",
            "HTTP_ACCEPT" => "application/json",
            "HTTP_CONTENT_TYPE" => "application/x-www-form-urlencoded",
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
        $attributes = ["guinea_pig" => "Claude"];
        $request = ServerRequest::getServerRequest($attributes);
        $this->assertNotNull($request);
        return $request;
    }

    /**
     * @covers WellRESTed\Message\ServerRequest::getServerParams
     * @depends testGetServerRequestReadsFromRequest
     */
    public function testServerRequestProvidesServerParams($request)
    {
        /** @var ServerRequest $request */
        $this->assertEquals("localhost", $request->getServerParams()["HTTP_HOST"]);
    }

    /**
     * @covers WellRESTed\Message\ServerRequest::getCookieParams
     * @depends testGetServerRequestReadsFromRequest
     */
    public function testServerRequestProvidesCookieParams($request)
    {
        /** @var ServerRequest $request */
        $this->assertEquals("Molly", $request->getCookieParams()["cat"]);
    }

    /**
     * @covers WellRESTed\Message\ServerRequest::getQueryParams
     * @depends testGetServerRequestReadsFromRequest
     */
    public function testServerRequestProvidesQueryParams($request)
    {
        /** @var ServerRequest $request */
        $this->assertEquals("Claude", $request->getQueryParams()["guinea_pig"]);
    }

    /**
     * @covers WellRESTed\Message\ServerRequest::getFileParams
     * @depends testGetServerRequestReadsFromRequest
     */
    public function testServerRequestProvidesFilesParams($request)
    {
        /** @var ServerRequest $request */
        $this->assertEquals("MyFile.jpg", $request->getFileParams()["file"]["name"]);
    }

    /**
     * @covers WellRESTed\Message\ServerRequest::getHeader
     * @depends testGetServerRequestReadsFromRequest
     */
    public function testServerRequestProvidesHeaders($request)
    {
        /** @var ServerRequest $request */
        $this->assertEquals(["application/json"], $request->getHeader("Accept"));
    }

    public function testServerRequestProvidesBody()
    {
        $body = $this->prophesize('Psr\Http\Message\StreamableInterface');
        MockServerRequestTest::$bodyStream = $body->reveal();
        $request = MockServerRequestTest::getServerRequest();
        $this->assertSame($body->reveal(), $request->getBody());
    }

    /**
     * @covers WellRESTed\Message\ServerRequest::getAttribute
     * @depends testGetServerRequestReadsFromRequest
     */
    public function testServerRequestProvidesAttributesIfPassed($request)
    {
        /** @var ServerRequest $request */
        $this->assertEquals("Claude", $request->getAttribute("guinea_pig"));
    }

    /**
     * @covers WellRESTed\Message\ServerRequest::withCookieParams
     * @depends testGetServerRequestReadsFromRequest
     */
    public function testWithCookieParamsCreatesNewInstance($request1)
    {
        /** @var ServerRequest $request1 */
        $request2 = $request1->withCookieParams([
            "cat" => "Oscar"
        ]);
        $this->assertEquals("Molly", $request1->getCookieParams()["cat"]);
        $this->assertEquals("Oscar", $request2->getCookieParams()["cat"]);
    }

    /**
     * @covers WellRESTed\Message\ServerRequest::withQueryParams
     * @depends testGetServerRequestReadsFromRequest
     */
    public function testWithQueryParamsCreatesNewInstance($request1)
    {
        /** @var ServerRequest $request1 */
        $request2 = $request1->withQueryParams([
            "guinea_pig" => "Clyde"
        ]);
        $this->assertEquals("Claude", $request1->getQueryParams()["guinea_pig"]);
        $this->assertEquals("Clyde", $request2->getQueryParams()["guinea_pig"]);
    }

    /**
     * @covers WellRESTed\Message\ServerRequest::withParsedBody
     * @depends testGetServerRequestReadsFromRequest
     */
    public function testWithParsedBodyCreatesNewInstance($request1)
    {
        /** @var ServerRequest $request1 */
        $body1 = $request1->getParsedBody();

        $request2 = $request1->withParsedBody([
            "guinea_pig" => "Clyde"
        ]);
        $body2 = $request2->getParsedBody();

        $this->assertEquals("Bear", $body1["dog"]);
        $this->assertEquals("Clyde", $body2["guinea_pig"]);
    }

    /**
     * @covers WellRESTed\Message\ServerRequest::getServerRequest
     * @covers WellRESTed\Message\ServerRequest::getParsedBody
     * @preserveGlobalState disabled
     * @dataProvider formContentTypeProvider
     */
    public function testGetServerRequestParsesFormBody($contentType)
    {
        $_SERVER = [
            "HTTP_HOST" => "localhost",
            "HTTP_CONTENT_TYPE" => $contentType,
        ];
        $_COOKIE = [];
        $_FILES = [];
        $_POST = [
            "dog" => "Bear"
        ];
        $request = ServerRequest::getServerRequest();
        $this->assertEquals("Bear", $request->getParsedBody()["dog"]);
    }

    public function formContentTypeProvider()
    {
        return [
            ["application/x-www-form-urlencoded"],
            ["multipart/form-data"]
        ];
    }

    /**
     * @covers WellRESTed\Message\ServerRequest::__clone
     */
    public function testCloneMakesDeepCopiesOfParsedBody()
    {
        $body = (object) [
            "cat" => "Dog"
        ];

        $request1 = new ServerRequest();
        $request1 = $request1->withParsedBody($body);
        $request2 = $request1->withHeader("X-extra", "hello world");
        $this->assertEquals($request1->getParsedBody(), $request2->getParsedBody());
        $this->assertNotSame($request1->getParsedBody(), $request2->getParsedBody());
    }

    /**
     * @covers WellRESTed\Message\ServerRequest::withAttribute
     * @covers WellRESTed\Message\ServerRequest::getAttribute
     */
    public function testWithAttributeCreatesNewInstance()
    {
        $request = new ServerRequest();
        $request = $request->withAttribute("cat", "Molly");
        $this->assertEquals("Molly", $request->getAttribute("cat"));
    }

    /**
     * @covers WellRESTed\Message\ServerRequest::withAttribute
     */
    public function testWithAttributePreserversOtherAttributes()
    {
        $request = new ServerRequest();
        $request = $request->withAttribute("cat", "Molly");
        $request = $request->withAttribute("dog", "Bear");
        $this->assertEquals("Molly", $request->getAttribute("cat"));
        $this->assertEquals("Bear", $request->getAttribute("dog"));
    }

    /**
     * @covers WellRESTed\Message\ServerRequest::getAttribute
     */
    public function testGetAttributeReturnsDefaultIfNotSet()
    {
        $request = new ServerRequest();
        $this->assertEquals("Oscar", $request->getAttribute("cat", "Oscar"));
    }

    /**
     * @covers WellRESTed\Message\ServerRequest::withoutAttribute
     */
    public function testWithoutAttributeCreatesNewInstance()
    {
        $request = new ServerRequest();
        $request = $request->withAttribute("cat", "Molly");
        $request = $request->withoutAttribute("cat");
        $this->assertEquals("Oscar", $request->getAttribute("cat", "Oscar"));
    }

    /**
     * @covers WellRESTed\Message\ServerRequest::withoutAttribute
     */
    public function testWithoutAttributePreservesOtherAttributes()
    {
        $request = new ServerRequest();
        $request = $request->withAttribute("cat", "Molly");
        $request = $request->withAttribute("dog", "Bear");
        $request = $request->withoutAttribute("cat");
        $this->assertEquals("Bear", $request->getAttribute("dog"));
        $this->assertEquals("Oscar", $request->getAttribute("cat", "Oscar"));
    }

    /**
     * @covers WellRESTed\Message\ServerRequest::getAttributes
     */
    public function testGetAttributesReturnsAllAttributes()
    {
        $request = new ServerRequest();
        $request = $request->withAttribute("cat", "Molly");
        $request = $request->withAttribute("dog", "Bear");
        $attributes = $request->getAttributes();
        $this->assertEquals("Molly", $attributes["cat"]);
        $this->assertEquals("Bear", $attributes["dog"]);
    }

    /**
     * @covers WellRESTed\Message\ServerRequest::getServerRequestHeaders
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testReadsApacheRequestHeaders()
    {
        // This file adds a dummy apache_request_headers in the global namespace.
        require_once(__DIR__ . "/../../../src/apache_request_headers.php");

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

    /**
     * @covers WellRESTed\Message\ServerRequest::readFromServerRequest
     * @preserveGlobalState disabled
     * @dataProvider protocolVersionProvider
     */
    public function testReadsProtocolVersionFromFromRequest($expectedProtocol, $serverProtocol)
    {
        $_SERVER = [
            "HTTP_HOST" => "localhost",
            "SERVER_PROTOCOL" => $serverProtocol,
            "REQUEST_METHOD" => "GET"
        ];
        $request = ServerRequest::getServerRequest();
        $this->assertEquals($expectedProtocol, $request->getProtocolVersion());
    }

    public function protocolVersionProvider()
    {
        return [
            ["1.1", "HTTP/1.1"],
            ["1.0", "HTTP/1.0"],
            ["1.1", null],
            ["1.1", "INVALID"]
        ];
    }

    /**
     * @covers WellRESTed\Message\ServerRequest::readFromServerRequest
     * @preserveGlobalState disabled
     * @dataProvider methodProvider
     */
    public function testReadsMethodFromFromRequest($exectedMethod, $serverMethod)
    {
        $_SERVER = [
            "HTTP_HOST" => "localhost",
            "REQUEST_METHOD" => $serverMethod
        ];
        $request = ServerRequest::getServerRequest();
        $this->assertEquals($exectedMethod, $request->getMethod());
    }

    public function methodProvider()
    {
        return [
            ["GET", "GET"],
            ["POST", "POST"],
            ["DELETE", "DELETE"],
            ["PUT", "PUT"],
            ["OPTIONS", "OPTIONS"],
            ["GET", null]
        ];
    }

    /**
     * @covers WellRESTed\Message\ServerRequest::readFromServerRequest
     * @preserveGlobalState disabled
     * @dataProvider requestTargetProvider
     */
    public function testReadsRequestTargetFromServer($exectedRequestTarget, $serverRequestUri)
    {
        $_SERVER = [
            "HTTP_HOST" => "localhost",
            "REQUEST_URI" => $serverRequestUri
        ];
        $request = ServerRequest::getServerRequest();
        $this->assertEquals($exectedRequestTarget, $request->getRequestTarget());
    }

    public function requestTargetProvider()
    {
        return [
            ["/", "/"],
            ["/hello", "/hello"],
            ["/my/path.txt", "/my/path.txt"],
            ["/", null]
        ];
    }
}

// ----------------------------------------------------------------------------

class MockServerRequestTest extends ServerRequest
{
    public static $bodyStream;

    protected function getStreamForBody()
    {
        return self::$bodyStream;
    }
}
