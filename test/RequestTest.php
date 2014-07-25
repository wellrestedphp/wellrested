<?php

use pjdietz\WellRESTed\Request;
use pjdietz\WellRESTed\Test;

class RequestBuilderTest extends \PHPUnit_Framework_TestCase
{
    /** @var Request */
    private $request;

    public function setUp()
    {
        $this->request = new Request();
        foreach ($this->headerProvider() as $item) {
            $name = $item[0];
            $value = $item[1];
            $this->request->setHeader($name, $value);
        }
    }

    public function headerProvider()
    {
        return array(
            array("Accept-Charset", "utf-8", "accept-charset"),
            array("Accept-Encoding", "gzip, deflate", "ACCEPT-ENCODING"),
            array("Cache-Control", "no-cache", "Cache-Control"),
        );
    }

    public function testSetBody()
    {
        $body = "This is the body";
        $rqst = new Request();
        $rqst->setBody($body);
        $this->assertEquals($body, $rqst->getBody());
    }

    public function testNullBody()
    {
        $this->assertNull($this->request->getBody());
    }

    public function testHeaders()
    {
        $this->assertEquals(3, count($this->request->getHeaders()));
    }

    /**
     * @dataProvider headerProvider
     */
    public function testHeaderValue($name, $value, $testName)
    {
        $this->assertEquals($value, $this->request->getHeader($testName));
    }

    /**
     * @dataProvider headerProvider
     */
    public function testNonsetHeader()
    {
        $this->assertNull($this->request->getHeader("no-header"));
    }

    /**
     * @dataProvider headerProvider
     */
    public function testUnsetHeader($name, $value, $testName)
    {
        $this->request->unsetHeader($testName);
        $this->assertNull($this->request->getHeader($testName));
    }

    /**
     * @dataProvider headerProvider
     */
    public function testUpdateHeader($name, $value, $testName)
    {
        $newvalue = "newvalue";
        $this->request->setHeader($testName, "newvalue");
        $this->assertEquals($newvalue, $this->request->getHeader($testName));
    }

    /**
     * @dataProvider headerProvider
     */
    public function testIssetHeader($name, $value, $testName)
    {
        $this->assertTrue($this->request->issetHeader($testName));
    }

    /**
     * @dataProvider headerProvider
     */
    public function testNotIssetHeader($name, $value, $testName)
    {
        $this->request->unsetHeader($testName);
        $this->assertFalse($this->request->issetHeader($testName));
    }

    /**
     * @dataProvider uriProvider
     */
    public function testUri($uri, $data)
    {
        $rqst = new Request($uri);
        $this->assertEquals($data->uri, $rqst->getUri());
    }

    /**
     * @dataProvider uriProvider
     */
    public function testScheme($uri, $data)
    {
        $rqst = new Request($uri);
        $this->assertEquals($data->scheme, $rqst->getScheme());
    }

    /**
     * @dataProvider uriProvider
     */
    public function testHostname($uri, $data)
    {
        $rqst = new Request($uri);
        $this->assertEquals($data->hostname, $rqst->getHostname());
    }

    /**
     * @dataProvider uriProvider
     */
    public function testPort($uri, $data)
    {
        $rqst = new Request($uri);
        $this->assertEquals($data->port, $rqst->getPort());
    }

    /**
     * @dataProvider uriProvider
     */
    public function testPath($uri, $data)
    {
        $rqst = new Request($uri);
        $this->assertEquals($data->path, $rqst->getPath());
    }

    /**
     * @dataProvider uriProvider
     */
    public function testPathParts($uri, $data)
    {
        $rqst = new Request($uri);
        $this->assertEquals($data->parts, $rqst->getPathParts());
    }

    /**
     * @dataProvider uriProvider
     */
    public function testQuery($uri, $data)
    {
        $rqst = new Request($uri);
        $this->assertEquals($data->query, $rqst->getQuery());
    }

    public function uriProvider()
    {
        return array(
            array(
                "http://www.google.com",
                (object) [
                    "uri" => "http://www.google.com",
                    "scheme" => "http",
                    "hostname" => "www.google.com",
                    "port" => 80,
                    "path" => "/",
                    "query" => [],
                    "parts" => []
                ]
            ),
            array(
                "https://www.google.com",
                (object) [
                    "uri" => "https://www.google.com",
                    "scheme" => "https",
                    "hostname" => "www.google.com",
                    "port" => 443,
                    "path" => "/",
                    "query" => [],
                    "parts" => []
                ]
            ),
            array(
                "localhost:8080/my/path/with/parts",
                (object) [
                    "uri" => "http://localhost:8080/my/path/with/parts",
                    "scheme" => "http",
                    "hostname" => "localhost",
                    "port" => 8080,
                    "path" => "/my/path/with/parts",
                    "query" => [],
                    "parts" => ["my", "path", "with", "parts"]
                ]
            ),
            array(
                "localhost?dog=bear&cat=molly",
                (object) [
                    "uri" => "http://localhost?cat=molly&dog=bear",
                    "scheme" => "http",
                    "hostname" => "localhost",
                    "port" => 80,
                    "path" => "/",
                    "query" => [
                        "cat" => "molly",
                        "dog" => "bear"
                    ],
                    "parts" => []
                ]
            ),
            array(
                "/my-page?id=2",
                (object) [
                    "uri" => "http://localhost/my-page?id=2",
                    "scheme" => "http",
                    "hostname" => "localhost",
                    "port" => 80,
                    "path" => "/my-page",
                    "query" => [
                        "id" => "2"
                    ],
                    "parts" => ["my-page"]
                ]
            )
        );
    }

    /**
     * @dataProvider defaultPortProvider
     */
    public function testDefaultPort($scheme, $port)
    {
        $rqst = new Request("http://localhost:9999");
        $rqst->setScheme($scheme);
        $rqst->setPort();
        $this->assertEquals($port, $rqst->getPort());
    }

    public function defaultPortProvider()
    {
        return [
            ["http", 80],
            ["https", 443]
        ];
    }

    /**
     * @dataProvider invalidSchemeProvider
     * @expectedException \UnexpectedValueException
     */
    public function testInvalidScheme($scheme)
    {
        $this->request->setScheme($scheme);
    }

    public function invalidSchemeProvider()
    {
        return [
            [""],
            ["ftp"],
            ["ssh"],
            [null],
            [0]
        ];
    }

    /**
     * @dataProvider queryProvider
     */
    public function testSetQuery($input, $expected)
    {
        $this->request->setQuery($input);
        $this->assertEquals($expected, $this->request->getQuery());
    }

    public function queryProvider()
    {
        return [
            [
                "cat=molly&dog=bear",
                [
                    "cat" => "molly",
                    "dog" => "bear"
                ]
            ],
            [
                ["id" => "1"],
                ["id" => "1"]
            ],
            [
                (object)["dog" => "bear"],
                ["dog" => "bear"]
            ],
            ["", []],
            [[], []],
        ];
    }

    /**
     * @dataProvider invalidQueryProvider
     * @expectedException  \InvalidArgumentException
     */
    public function testInvalidQuery($query)
    {
        $this->request->setQuery($query);
    }

    public function invalidQueryProvider()
    {
        return [
            [11],
            [false],
            [true],
            [null]
        ];
    }

    /**
     * @dataProvider methodProvider
     */
    public function testMethod($method)
    {
        $this->request->setMethod($method);
        $this->assertEquals($method, $this->request->getMethod());
    }

    public function methodProvider()
    {
        return array(
            array("GET"),
            array("POST"),
            array("PUT"),
            array("DELETE"),
            array("OPTIONS"),
            array("HEAD")
        );
    }

    /**
     * @dataProvider serverProvider
     */
    public function testServerRequestMethod($serverVars, $expected)
    {
        $original = $_SERVER;
        $_SERVER = array_merge($_SERVER, $serverVars);
        $rqst = new Request();
        $rqst->readHttpRequest();
        $this->assertEquals($expected->method, $rqst->getMethod());
        $_SERVER = $original;
    }

    /**
     * @dataProvider serverProvider
     */
    public function testServerRequestHost($serverVars, $expected)
    {
        $original = $_SERVER;
        $_SERVER = array_merge($_SERVER, $serverVars);
        $rqst = new Request();
        $rqst->readHttpRequest();
        $this->assertEquals($expected->host, $rqst->getHostname());
        $_SERVER = $original;
    }

    /**
     * @dataProvider serverProvider
     */
    public function testServerRequestPath($serverVars, $expected)
    {
        $original = $_SERVER;
        $_SERVER = array_merge($_SERVER, $serverVars);
        $rqst = new Request();
        $rqst->readHttpRequest();
        $this->assertEquals($expected->path, $rqst->getPath());
        $_SERVER = $original;
    }

    /**
     * @dataProvider serverProvider
     */
    public function testServerRequestHeaders($serverVars, $expected)
    {
        $original = $_SERVER;
        $_SERVER = array_merge($_SERVER, $serverVars);
        $rqst = new Request();
        $rqst->readHttpRequest();
        foreach ($expected->headers as $name => $value) {
            $this->assertEquals($value, $rqst->getHeader($name));
        }
        $_SERVER = $original;
    }

    /**
     * @dataProvider serverProvider
     */
    public function testHasApacheHeaders($serverVars, $expected)
    {
        if (!function_exists('apache_request_headers')) {
            function apache_request_headers() {
                $headers = '';
                foreach ($_SERVER as $name => $value) {
                    if (substr($name, 0, 5) === 'HTTP_') {
                        $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                    }
                }
                return $headers;
            }
        }

        $original = $_SERVER;
        $_SERVER = array_merge($_SERVER, $serverVars);
        $rqst = new Request();
        $rqst->readHttpRequest();
        foreach ($expected->headers as $name => $value) {
            $this->assertEquals($value, $rqst->getHeader($name));
        }
        $_SERVER = $original;
    }

    /**
     * We can only test the static member once, so no need for dataProvider.
     */
    public function testStaticRequest()
    {
        $data = $this->serverProvider();
        $serverVars = $data[0][0];
        $expected = $data[0][1];

        $original = $_SERVER;
        $_SERVER = array_merge($_SERVER, $serverVars);
        $rqst = Request::getRequest();
        $this->assertEquals($expected->host, $rqst->getHostname());

        $_SERVER = $original;

        return $rqst;
    }

    /**
     * @depends testStaticRequest
     */
    public function testStaticRequestAgain($previousRequest)
    {
        $rqst = Request::getRequest();
        $this->assertSame($previousRequest, $rqst);
    }

    public function serverProvider()
    {
        return [
            [
                [
                    "REQUEST_METHOD" => "GET",
                    "REQUEST_URI" => "/",
                    "HTTP_ACCEPT_CHARSET" => "utf-8",
                    "HTTP_HOST" => "localhost"
                ],
                (object) [
                    "method" => "GET",
                    "host" => "localhost",
                    "path" => "/",
                    "headers" => [
                        "Accept-charset" => "utf-8"
                    ]
                ]
            ],
            [
                [
                    "REQUEST_METHOD" => "POST",
                    "REQUEST_URI" => "/my/page",
                    "HTTP_ACCEPT_CHARSET" => "utf-8",
                    "HTTP_HOST" => "mysite.com"
                ],
                (object) [
                    "method" => "POST",
                    "host" => "mysite.com",
                    "path" => "/my/page",
                    "headers" => [
                        "Accept-charset" => "utf-8"
                    ]
                ]
            ]
        ];
    }

}
