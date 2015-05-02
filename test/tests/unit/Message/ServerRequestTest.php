<?php

namespace WellRESTed\Test\Unit\Message;

use WellRESTed\Message\ServerRequest;
use WellRESTed\Message\UploadedFile;
use WellRESTed\Message\Uri;

// TODO Test nested $_FILES with associative array for last level
// TODO Remove concrete class used for testing

/**
 * @coversDefaultClass WellRESTed\Message\ServerRequest
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
     * @covers ::__construct
     */
    public function testCreatesInstance()
    {
        $request = new ServerRequest();
        $this->assertNotNull($request);
    }

    /**
     * @covers ::getServerRequest
     * @covers ::getServerRequestHeaders
     * @covers ::readFromServerRequest
     * @covers ::getStreamForBody
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
     * @covers ::readFromServerRequest
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
     * @covers ::readFromServerRequest
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
     * @covers ::readFromServerRequest
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
     * @covers ::getHeader
     * @depends testGetServerRequestReadsFromRequest
     */
    public function testServerRequestProvidesHeaders($request)
    {
        /** @var ServerRequest $request */
        $this->assertEquals(["application/json"], $request->getHeader("Accept"));
    }

    /**
     * @covers ::getBody
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
     * @covers ::getServerParams
     */
    public function testServerParamsIsEmptyByDefault()
    {
        $request = new ServerRequest();
        $this->assertEquals([], $request->getServerParams());
    }

    /**
     * @covers ::getServerParams
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
     * @covers ::getCookieParams
     */
    public function testCookieParamsIsEmptyByDefault()
    {
        $request = new ServerRequest();
        $this->assertEquals([], $request->getCookieParams());
    }

    /**
     * @covers ::getCookieParams
     * @depends testGetServerRequestReadsFromRequest
     */
    public function testServerRequestProvidesCookieParams($request)
    {
        /** @var ServerRequest $request */
        $this->assertEquals("Molly", $request->getCookieParams()["cat"]);
    }

    /**
     * @covers ::withCookieParams
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
     * @covers ::getQueryParams
     */
    public function testQueryParamsIsEmptyByDefault()
    {
        $request = new ServerRequest();
        $this->assertEquals([], $request->getQueryParams());
    }

    /**
     * @covers ::getQueryParams
     * @depends testGetServerRequestReadsFromRequest
     */
    public function testServerRequestProvidesQueryParams($request)
    {
        /** @var ServerRequest $request */
        $this->assertEquals("Claude", $request->getQueryParams()["guinea_pig"]);
    }

    /**
     * @covers ::withQueryParams
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
     * @covers ::getUploadedFiles
     */
    public function testUploadedFilesIsEmptyByDefault()
    {
        $request = new ServerRequest();
        $this->assertEquals([], $request->getUploadedFiles());
    }

    /**
     * @covers ::getUploadedFiles
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
     * @covers ::getServerRequest
     * @covers ::readUploadedFiles
     * @covers ::getUploadedFiles
     * @covers ::addUploadedFilesToBranch
     * @preserveGlobalState disabled
     * @dataProvider uploadedFileProvider
     */
    public function testGetServerRequestProvidesUploadedFiles($file, $path)
    {
        $_SERVER = [
            "HTTP_HOST" => "localhost",
            "HTTP_ACCEPT" => "application/json",
            "HTTP_CONTENT_TYPE" => "application/x-www-form-urlencoded"
        ];
        $_FILES = [
            "single" => [
                "name" => "single.txt",
                "type" => "text/plain",
                "tmp_name" => "/tmp/php9hNlHe",
                "error" => UPLOAD_ERR_OK,
                "size" => 524
            ],
            "nested" => [
                "level2" => [
                    "name" => "nested.json",
                    "type" => "application/json",
                    "tmp_name" => "/tmp/phpadhjk",
                    "error" => UPLOAD_ERR_OK,
                    "size" => 1024
                ]
            ],
            "nestedList" => [
                "level2" => [
                    "name" => [
                        0 => "nestedList0.jpg",
                        1 => "nestedList1.jpg",
                        2 => ""
                    ],
                    "type" => [
                        0 => "image/jpeg",
                        1 => "image/jpeg",
                        2 => ""
                    ],
                    "tmp_name" => [
                        0 => "/tmp/phpjpg0",
                        1 => "/tmp/phpjpg1",
                        2 => ""
                    ],
                    "error" => [
                        0 => UPLOAD_ERR_OK,
                        1 => UPLOAD_ERR_OK,
                        2 => UPLOAD_ERR_NO_FILE
                    ],
                    "size" => [
                        0 => 256,
                        1 => 4096,
                        2 => 0
                    ]
                ]
            ],
            "nestedDictionary" => [
                "level2" => [
                    "name" => [
                        "file0" => "nestedDictionary0.jpg",
                        "file1" => "nestedDictionary1.jpg"
                    ],
                    "type" => [
                        "file0" => "image/png",
                        "file1" => "image/png"
                    ],
                    "tmp_name" => [
                        "file0" => "/tmp/phppng0",
                        "file1" => "/tmp/phppng1"
                    ],
                    "error" => [
                        "file0" => UPLOAD_ERR_OK,
                        "file1" => UPLOAD_ERR_OK
                    ],
                    "size" => [
                        "file0" => 256,
                        "file1" => 4096
                    ]
                ]
            ]
        ];
        $request = ServerRequest::getServerRequest();
        $current = $request->getUploadedFiles();
        foreach ($path as $item) {
            $current = $current[$item];
        }
        $this->assertEquals($file, $current);
    }

    public function uploadedFileProvider()
    {
        return [
            [new UploadedFile("single.txt", "text/plain", 524, "/tmp/php9hNlHe", UPLOAD_ERR_OK), ["single"]],
            [new UploadedFile("nested.json", "application/json", 1024, "/tmp/phpadhjk", UPLOAD_ERR_OK), ["nested", "level2"]],
            [new UploadedFile("nestedList0.jpg", "image/jpeg", 256, "/tmp/phpjpg0", UPLOAD_ERR_OK), ["nestedList", "level2", 0]],
            [new UploadedFile("nestedList1.jpg", "image/jpeg", 4096, "/tmp/phpjpg1", UPLOAD_ERR_OK), ["nestedList", "level2", 1]],
            [new UploadedFile("", "", 0, "", UPLOAD_ERR_NO_FILE), ["nestedList", "level2", 2]],
            [new UploadedFile("nestedDictionary0.jpg", "image/png", 256, "/tmp/phppng0", UPLOAD_ERR_OK), ["nestedDictionary", "level2", "file0"]],
            [new UploadedFile("nestedDictionary1.jpg", "image/png", 4096, "/tmp/phppngg1", UPLOAD_ERR_OK), ["nestedDictionary", "level2", "file1"]]
        ];
    }

    /**
     * @covers ::withUploadedFiles
     * @covers ::isValidUploadedFilesBranch
     * @covers ::isValidUploadedFilesTree
     */
    public function testWithUploadedFilesCreatesNewInstance()
    {
        $uploadedFiles = [
            "file" => new UploadedFile("index.html", "text/html", 524, "/tmp/php9hNlHe", 0)
        ];
        $request = new ServerRequest();
        $request1 = $request->withUploadedFiles([]);
        $request2 = $request1->withUploadedFiles($uploadedFiles);
        $this->assertNotSame($request2, $request1);
    }

    /**
     * @covers ::withUploadedFiles
     * @covers ::isValidUploadedFilesTree
     * @covers ::isValidUploadedFilesBranch
     * @dataProvider validUploadedFilesProvider
     */
    public function testWithUploadedFilesReturnsPassedUploadedFiles($uploadedFiles)
    {
        $request = new ServerRequest();
        $request = $request->withUploadedFiles($uploadedFiles);
        $this->assertSame($uploadedFiles, $request->getUploadedFiles());
    }

    public function validUploadedFilesProvider()
    {
        return [
            [[]],
            [["files" => new UploadedFile("index.html", "text/html", 524, "/tmp/php9hNlHe", 0)]],
            [["nested" => [
                "level2" => new UploadedFile("index.html", "text/html", 524, "/tmp/php9hNlHe", 0)
            ]]],
            [["nestedList" => [
                "level2" => [
                    new UploadedFile("file1.html", "text/html", 524, "/tmp/php9hNlHe", 0),
                    new UploadedFile("file2.html", "text/html", 524, "/tmp/php9hNshj", 0)
                ]
            ]]],
            [["nestedDictionary" => [
                "level2" => [
                    "file1" => new UploadedFile("file1.html", "text/html", 524, "/tmp/php9hNlHe", 0),
                    "file2" => new UploadedFile("file2.html", "text/html", 524, "/tmp/php9hNshj", 0)
                ]
            ]]]
        ];
    }

    /**
     * @covers ::withUploadedFiles
     * @covers ::isValidUploadedFilesTree
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
            [
                [new UploadedFile("index1.html", "text/html", 524, "/tmp/php9hNlHe", 0)],
                [new UploadedFile("index2.html", "text/html", 524, "/tmp/php9hNlHe", 0)]
            ],
            [
                "single" => [
                    "name" => "single.txt",
                    "type" => "text/plain",
                    "tmp_name" => "/tmp/php9hNlHe",
                    "error" => UPLOAD_ERR_OK,
                    "size" => 524
                ],
                "nested" => [
                    "level2" => [
                        "name" => "nested.json",
                        "type" => "application/json",
                        "tmp_name" => "/tmp/phpadhjk",
                        "error" => UPLOAD_ERR_OK,
                        "size" => 1024
                    ]
                ],
                "nestedList" => [
                    "level2" => [
                        "name" => [
                            0 => "nestedList0.jpg",
                            1 => "nestedList1.jpg",
                            2 => ""
                        ],
                        "type" => [
                            0 => "image/jpeg",
                            1 => "image/jpeg",
                            2 => ""
                        ],
                        "tmp_name" => [
                            0 => "/tmp/phpjpg0",
                            1 => "/tmp/phpjpg1",
                            2 => ""
                        ],
                        "error" => [
                            0 => UPLOAD_ERR_OK,
                            1 => UPLOAD_ERR_OK,
                            2 => UPLOAD_ERR_NO_FILE
                        ],
                        "size" => [
                            0 => 256,
                            1 => 4096,
                            2 => 0
                        ]
                    ]
                ]
            ]
        ];
    }

    // ------------------------------------------------------------------------
    // Parsed Body

    /**
     * @covers ::getParsedBody
     */
    public function testParsedBodyIsNullByDefault()
    {
        $request = new ServerRequest();
        $this->assertNull($request->getParsedBody());
    }

    /**
     * @covers ::getServerRequest
     * @covers ::getParsedBody
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
     * @covers ::withParsedBody
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
     * @covers ::withParsedBody
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
     * @covers ::__clone
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
     * @covers ::getAttributes
     */
    public function testAttributesIsEmptyByDefault()
    {
        $request = new ServerRequest();
        $this->assertEquals([], $request->getAttributes());
    }

    /**
     * @covers ::getAttribute
     * @depends testGetServerRequestReadsFromRequest
     */
    public function testServerRequestProvidesAttributesIfPassed($request)
    {
        /** @var ServerRequest $request */
        $this->assertEquals("Claude", $request->getAttribute("guinea_pig"));
    }

    /**
     * @covers ::getAttribute
     */
    public function testGetAttributeReturnsDefaultIfNotSet()
    {
        $request = new ServerRequest();
        $this->assertEquals("Oscar", $request->getAttribute("cat", "Oscar"));
    }

    /**
     * @covers ::withAttribute
     * @covers ::getAttribute
     */
    public function testWithAttributeCreatesNewInstance()
    {
        $request = new ServerRequest();
        $request = $request->withAttribute("cat", "Molly");
        $this->assertEquals("Molly", $request->getAttribute("cat"));
    }

    /**
     * @covers ::withAttribute
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
     * @covers ::withoutAttribute
     */
    public function testWithoutAttributeCreatesNewInstance()
    {
        $request = new ServerRequest();
        $request = $request->withAttribute("cat", "Molly");
        $request = $request->withoutAttribute("cat");
        $this->assertEquals("Oscar", $request->getAttribute("cat", "Oscar"));
    }

    /**
     * @covers ::withoutAttribute
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
     * @covers ::getAttributes
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
     * @covers ::getServerRequest
     * @covers ::getServerRequestHeaders
     * @covers ::readFromServerRequest
     * @covers ::readUri
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
