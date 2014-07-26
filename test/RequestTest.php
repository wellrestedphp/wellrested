<?php

namespace pjdietz\WellRESTed\Test;

use Faker\Factory;
use pjdietz\WellRESTed\Request;
use pjdietz\WellRESTed\Test;

class RequestTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider methodProvider
     */
    public function testSetMethod($method)
    {
        $rqst = new Request();
        $rqst->setMethod($method);
        $this->assertEquals($method, $rqst->getMethod());
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
     * @dataProvider uriProvider
     */
    public function testSetUri($uri, $data)
    {
        $rqst = new Request($uri);
        $this->assertEquals($data->uri, $rqst->getUri());
    }

    /**
     * @dataProvider uriProvider
     */
    public function testParseSchemeFromUri($uri, $data)
    {
        $rqst = new Request($uri);
        $this->assertEquals($data->scheme, $rqst->getScheme());
    }

    /**
     * @dataProvider uriProvider
     */
    public function testParseHostnameFromUri($uri, $data)
    {
        $rqst = new Request($uri);
        $this->assertEquals($data->hostname, $rqst->getHostname());
    }

    /**
     * @dataProvider uriProvider
     */
    public function testParsePortFromUri($uri, $data)
    {
        $rqst = new Request($uri);
        $this->assertEquals($data->port, $rqst->getPort());
    }

    /**
     * @dataProvider uriProvider
     */
    public function testParsePathFromUri($uri, $data)
    {
        $rqst = new Request($uri);
        $this->assertEquals($data->path, $rqst->getPath());
    }

    /**
     * @dataProvider uriProvider
     */
    public function testParsePathPartsFromUri($uri, $data)
    {
        $rqst = new Request($uri);
        $this->assertEquals($data->parts, $rqst->getPathParts());
    }

    /**
     * @dataProvider uriProvider
     */
    public function testParseQueryFromUri($uri, $data)
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

    public function testSetBody()
    {
        $body = "This is the body";
        $rqst = new Request();
        $rqst->setBody($body);
        $this->assertEquals($body, $rqst->getBody());
    }

    public function testBodyIsNullByDefault()
    {
        $rqst = new Request();
        $this->assertNull($rqst->getBody());
    }

    public function testSetFormFields()
    {
        $faker = Factory::create();
        $form = [
            "firstName" => $faker->firstName,
            "lastName" => $faker->lastName,
            "username" => $faker->userName
        ];

        $rqst = new Request();
        $rqst->setFormFields($form);
        $body = $rqst->getBody();
        parse_str($body, $fields);
        $this->assertEquals($form, $fields);
    }

    /**
     * @dataProvider headerProvider
     */
    public function testSetHeader($headerKey, $headerValue, $badCapsKey)
    {
        $rqst = new Request();
        $rqst->setHeader($headerKey, $headerValue);
        $this->assertEquals($headerValue, $rqst->getHeader($badCapsKey));
    }

    /**
     * @dataProvider headerProvider
     */
    public function testUpdateHeader($headerKey, $headerValue, $testName)
    {
        $rqst = new Request();
        $rqst->setHeader($headerKey, $headerValue);
        $newValue = "newvalue";
        $rqst->setHeader($testName, "newvalue");
        $this->assertEquals($newValue, $rqst->getHeader($testName));
    }

    /**
     * @dataProvider headerProvider
     */
    public function testNonsetHeaderIsNull()
    {
        $rqst = new Request();
        $this->assertNull($rqst->getHeader("no-header"));
    }

    /**
     * @dataProvider headerProvider
     */
    public function testUnsetHeaderIsNull($headerKey, $headerValue, $testName)
    {
        $rqst = new Request();
        $rqst->setHeader($headerKey, $headerValue);
        $rqst->unsetHeader($testName);
        $this->assertNull($rqst->getHeader($headerKey));
    }

    /**
     * @dataProvider headerProvider
     */
    public function testCheckIfHeaderIsSet($headerKey, $headerValue, $testName)
    {
        $rqst = new Request();
        $rqst->setHeader($headerKey, $headerValue);
        $this->assertTrue($rqst->issetHeader($testName));
    }

    public function headerProvider()
    {
        return array(
            array("Accept-Charset", "utf-8", "accept-charset"),
            array("Accept-Encoding", "gzip, deflate", "ACCEPT-ENCODING"),
            array("Cache-Control", "no-cache", "Cache-Control"),
        );
    }

    public function testCountHeader()
    {
        $rqst = new Request();
        $headers = $this->headerProvider();
        foreach ($headers as $header) {
            $rqst->setHeader($header[0], $header[1]);
        }
        $this->assertEquals(count($headers), count($rqst->getHeaders()));
    }

    /**
     * @dataProvider queryProvider
     */
    public function testSetQuery($input, $expected)
    {
        $rqst = new Request();
        $rqst->setQuery($input);
        $this->assertEquals($expected, $rqst->getQuery());
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
    public function testFailOnInvalidQuery($query)
    {
        $rqst = new Request();
        $rqst->setQuery($query);
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
     * @dataProvider invalidSchemeProvider
     * @expectedException \UnexpectedValueException
     */
    public function testFailOnInvalidScheme($scheme)
    {
        $rqst = new Request();
        $rqst->setScheme($scheme);
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
     * @dataProvider defaultPortProvider
     */
    public function testSetDefaultPort($scheme, $port)
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
     * @dataProvider serverProvider
     */
    public function testReadServerRequestMethod($serverVars, $expected)
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
    public function testReadServerRequestHost($serverVars, $expected)
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
    public function testReadServerRequestPath($serverVars, $expected)
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
    public function testReadServerRequestHeaders($serverVars, $expected)
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
     * We can only test the static member once, so no need for dataProvider.
     */
    public function testReadStaticRequest()
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
     * @depends testReadStaticRequest
     */
    public function testReadStaticRequestAgain($previousRequest)
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
