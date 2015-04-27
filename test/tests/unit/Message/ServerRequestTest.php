<?php

namespace WellRESTed\Test\Unit\Message;

use WellRESTed\Message\ServerRequest;
use WellRESTed\Message\UploadedFile;
use WellRESTed\Message\Uri;

/**
 * @uses WellRESTed\Message\ServerRequest
 * @uses WellRESTed\Message\Request
 * @uses WellRESTed\Message\Message
 * @uses WellRESTed\Message\HeaderCollection
 * @uses WellRESTed\Message\Stream
 * @uses WellRESTed\Message\UploadedFile
 * @uses WellRESTed\Message\Uri
 */
class ServerRequestTest extends \PHPUnit_Framework_TestCase
{
    // ------------------------------------------------------------------------
    // Construction and Marshalling

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
        $_FILES = [];
        $_POST = [
            "dog" => "Bear"
        ];
        $attributes = ["guinea_pig" => "Claude"];
        $request = ServerRequest::getServerRequest($attributes);
        $this->assertNotNull($request);
        return $request;
    }

    // ------------------------------------------------------------------------
    // Request

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

    /**
     * @covers WellRESTed\Message\ServerRequest::getHeader
     * @depends testGetServerRequestReadsFromRequest
     */
    public function testServerRequestProvidesHeaders($request)
    {
        /** @var ServerRequest $request */
        $this->assertEquals(["application/json"], $request->getHeader("Accept"));
    }

    /**
     * @covers WellRESTed\Message\ServerRequest::getBody
     */
    public function testServerRequestProvidesBody()
    {
        $body = $this->prophesize('Psr\Http\Message\StreamInterface');
        MockServerRequest::$bodyStream = $body->reveal();
        $request = MockServerRequest::getServerRequest();
        $this->assertSame($body->reveal(), $request->getBody());
    }

    // ------------------------------------------------------------------------
    // Server Params

    /**
     * @covers WellRESTed\Message\ServerRequest::getServerParams
     */
    public function testServerParamsIsEmptyByDefault()
    {
        $request = new ServerRequest();
        $this->assertEquals([], $request->getServerParams());
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

    // ------------------------------------------------------------------------
    // Cookies

    /**
     * @covers WellRESTed\Message\ServerRequest::getCookieParams
     */
    public function testCookieParamsIsEmptyByDefault()
    {
        $request = new ServerRequest();
        $this->assertEquals([], $request->getCookieParams());
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

    // ------------------------------------------------------------------------
    // Query

    /**
     * @covers WellRESTed\Message\ServerRequest::getQueryParams
     */
    public function testQueryParamsIsEmptyByDefault()
    {
        $request = new ServerRequest();
        $this->assertEquals([], $request->getQueryParams());
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

    // ------------------------------------------------------------------------
    // Uploaded Files

    /**
     * @covers WellRESTed\Message\ServerRequest::getUploadedFiles
     */
    public function testUploadedFilesIsEmptyByDefault()
    {
        $request = new ServerRequest();
        $this->assertEquals([], $request->getUploadedFiles());
    }

    /**
     * @covers WellRESTed\Message\ServerRequest::getUploadedFiles
     * @preserveGlobalState disabled
     */
    public function testGetUploadedFilesReturnsEmptyArrayWhenNoFilesAreUploaded()
    {
        $_SERVER = [
            "HTTP_HOST" => "localhost",
            "HTTP_ACCEPT" => "application/json",
            "HTTP_CONTENT_TYPE" => "application/x-www-form-urlencoded"
        ];
        $_FILES = [];
        $request = ServerRequest::getServerRequest();
        $this->assertSame([], $request->getUploadedFiles());
    }

    /**
     * @covers WellRESTed\Message\ServerRequest::getServerRequest
     * @covers WellRESTed\Message\ServerRequest::readUploadedFiles
     * @covers WellRESTed\Message\ServerRequest::getUploadedFiles
     * @preserveGlobalState disabled
     * @dataProvider uploadedFileProvider
     */
    public function testGetServerRequestProvidesUploadedFiles($file, $name, $index)
    {
        $_SERVER = [
            "HTTP_HOST" => "localhost",
            "HTTP_ACCEPT" => "application/json",
            "HTTP_CONTENT_TYPE" => "application/x-www-form-urlencoded"
        ];
        $_FILES = [
            "file" => [
                "name" => "index.html",
                "type" => "text/html",
                "tmp_name" => "/tmp/php9hNlHe",
                "error" => 0,
                "size" => 524
            ],
            "fileList" => [
                "name" => [
                    "data.json",
                    ""
                ],
                "type" => [
                    "application/json",
                    ""
                ],
                "tmp_name" => [
                    "/tmp/phpUigZSO",
                    ""
                ],
                "error" => [
                    0,
                    4
                ],
                "size" => [
                    1024,
                    0
                ]
            ]
        ];
        $request = ServerRequest::getServerRequest();
        $this->assertEquals($file, $request->getUploadedFiles()[$name][$index]);
    }

    public function uploadedFileProvider()
    {
        return [
            [new UploadedFile("index.html", "text/html", 524, "/tmp/php9hNlHe", 0), "file", 0],
            [new UploadedFile("data.json", "application/json", 1024, "/tmp/phpUigZSO", 0), "fileList", 0],
            [new UploadedFile("", "", 0, "", 4), "fileList", 1]
        ];
    }

    /**
     * @covers WellRESTed\Message\ServerRequest::withUploadedFiles
     * @covers WellRESTed\Message\ServerRequest::isValidUploadedFilesTree
     */
    public function testWithUploadedFilesCreatesNewInstance()
    {
        $uploadedFiles = [
            "file" => [new UploadedFile("index.html", "text/html", 524, "/tmp/php9hNlHe", 0)]
        ];
        $request = new ServerRequest();
        $request1 = $request->withUploadedFiles([]);
        $request2 = $request1->withUploadedFiles($uploadedFiles);
        $this->assertNotSame($request2, $request1);
    }

    /**
     * @covers WellRESTed\Message\ServerRequest::withUploadedFiles
     * @covers WellRESTed\Message\ServerRequest::isValidUploadedFilesTree
     */
    public function testWithUploadedFilesReturnsPassedUploadedFiles()
    {
        $uploadedFiles = [
            "file" => [new UploadedFile("index.html", "text/html", 524, "/tmp/php9hNlHe", 0)]
        ];
        $request = new ServerRequest();
        $request = $request->withUploadedFiles($uploadedFiles);
        $this->assertSame($uploadedFiles, $request->getUploadedFiles());
    }

    /**
     * @covers WellRESTed\Message\ServerRequest::withUploadedFiles
     * @covers WellRESTed\Message\ServerRequest::isValidUploadedFilesTree
     * @expectedException \InvalidArgumentException
     * @dataProvider invalidUploadedFilesProvider
     */
    public function testWithUploadedFilesThrowsExceptionWithInvalidTree($uploadedFiles)
    {
        $request = new ServerRequest();
        $request->withUploadedFiles($uploadedFiles);
    }

    public function invalidUploadedFilesProvider()
    {
        return [
            // All keys must be strings
            [[new UploadedFile("index.html", "text/html", 524, "/tmp/php9hNlHe", 0)]],

            // All values must be arrays.
            [["file" => new UploadedFile("index.html", "text/html", 524, "/tmp/php9hNlHe", 0)]],

            // All values must be list arrays.
            [
                [
                    "file" =>
                        [
                            "file1" => new UploadedFile("index.html", "text/html", 524, "/tmp/php9hNlHe", 0)
                        ]
                ]
            ],
            [
                [
                    "file" => [
                        0 => new UploadedFile("index.html", "text/html", 524, "/tmp/php9hNlHe", 0),
                        2 => new UploadedFile("index.html", "text/html", 524, "/tmp/php9hNlHe", 0)
                    ]
                ]
            ],
            [
                [
                    "file" => [
                        new UploadedFile("index.html", "text/html", 524, "/tmp/php9hNlHe", 0),
                        new UploadedFile("index.html", "text/html", 524, "/tmp/php9hNlHe", 0),
                        "index.html"
                    ]
                ]
            ]
        ];
    }

    // ------------------------------------------------------------------------
    // Parsed Body

    /**
     * @covers WellRESTed\Message\ServerRequest::getParsedBody
     */
    public function testParsedBodyIsNullByDefault()
    {
        $request = new ServerRequest();
        $this->assertNull($request->getParsedBody());
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
     * @covers WellRESTed\Message\ServerRequest::withParsedBody
     * @expectedException \InvalidArgumentException
     * @dataProvider invalidParsedBodyProvider
     */
    public function testWithParsedBodyThrowsExceptionWithInvalidType($body)
    {
        $request = new ServerRequest();
        $request->withParsedBody($body);
    }

    public function invalidParsedBodyProvider()
    {
        return [
            [false],
            [1]
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

    // ------------------------------------------------------------------------
    // Attributes

    /**
     * @covers WellRESTed\Message\ServerRequest::getAttributes
     */
    public function testAttributesIsEmptyByDefault()
    {
        $request = new ServerRequest();
        $this->assertEquals([], $request->getAttributes());
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
     * @covers WellRESTed\Message\ServerRequest::getAttribute
     */
    public function testGetAttributeReturnsDefaultIfNotSet()
    {
        $request = new ServerRequest();
        $this->assertEquals("Oscar", $request->getAttribute("cat", "Oscar"));
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

    // ------------------------------------------------------------------------
    // URI

    /**
     * @covers WellRESTed\Message\ServerRequest::getServerRequest
     * @covers WellRESTed\Message\ServerRequest::getServerRequestHeaders
     * @covers WellRESTed\Message\ServerRequest::readFromServerRequest
     * @covers WellRESTed\Message\ServerRequest::readUri
     * @preserveGlobalState disabled
     * @dataProvider uriProvider
     */
    public function testGetServerRequestProvidesUri($expected, $server)
    {
        $_SERVER = $server;
        $request = ServerRequest::getServerRequest();
        $this->assertEquals($expected, $request->getUri());
    }

    public function uriProvider()
    {
        return [
            [
                new Uri("http://localhost/path"),
                [
                    "HTTPS" => "off",
                    "HTTP_HOST" => "localhost",
                    "REQUEST_URI" => "/path",
                    "QUERY_STRING" => ""
                ]
            ],
            [
                new Uri("https://foo.com/path/to/stuff?cat=molly"),
                [
                    "HTTPS" => "1",
                    "HTTP_HOST" => "foo.com",
                    "REQUEST_URI" => "/path/to/stuff?cat=molly",
                    "QUERY_STRING" => "cat=molly"
                ]
            ],
            [
                new Uri("http://foo.com:8080/path/to/stuff?cat=molly"),
                [
                    "HTTP" => "1",
                    "HTTP_HOST" => "foo.com:8080",
                    "REQUEST_URI" => "/path/to/stuff?cat=molly",
                    "QUERY_STRING" => "cat=molly"
                ]
            ]
        ];
    }
}

// ----------------------------------------------------------------------------

class MockServerRequest extends ServerRequest
{
    public static $bodyStream;

    protected function getStreamForBody()
    {
        return self::$bodyStream;
    }
}
