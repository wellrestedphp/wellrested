<?php

namespace pjdietz\WellRESTed\Test;

use Faker\Factory;
use pjdietz\WellRESTed\Request;
use pjdietz\WellRESTed\Test;

/**
 * @covers pjdietz\WellRESTed\Request
 */
class RequestTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider methodProvider
     */
    public function testSetsMethod($method)
    {
        $rqst = new Request();
        $rqst->setMethod($method);
        $this->assertEquals($method, $rqst->getMethod());
    }

    public function methodProvider()
    {
        return [
            ["GET"],
            ["POST"],
            ["PUT"],
            ["DELETE"],
            ["OPTIONS"],
            ["HEAD"]
        ];
    }

    /**
     * @dataProvider uriProvider
     */
    public function testSetsUri($uri, $data)
    {
        $rqst = new Request($uri);
        $this->assertEquals($data->uri, $rqst->getUri());
    }

    /**
     * @dataProvider uriProvider
     */
    public function testParsesSchemeFromUri($uri, $data)
    {
        $rqst = new Request($uri);
        $this->assertEquals($data->scheme, $rqst->getScheme());
    }

    /**
     * @dataProvider uriProvider
     */
    public function testParsesHostnameFromUri($uri, $data)
    {
        $rqst = new Request($uri);
        $this->assertEquals($data->hostname, $rqst->getHostname());
    }

    /**
     * @dataProvider uriProvider
     */
    public function testParsesPortFromUri($uri, $data)
    {
        $rqst = new Request($uri);
        $this->assertEquals($data->port, $rqst->getPort());
    }

    /**
     * @dataProvider uriProvider
     */
    public function testParsesPathFromUri($uri, $data)
    {
        $rqst = new Request($uri);
        $this->assertEquals($data->path, $rqst->getPath());
    }

    /**
     * @dataProvider uriProvider
     */
    public function testParsesPathPartsFromUri($uri, $data)
    {
        $rqst = new Request($uri);
        $this->assertEquals($data->parts, $rqst->getPathParts());
    }

    /**
     * @dataProvider uriProvider
     */
    public function testParsesQueryFromUri($uri, $data)
    {
        $rqst = new Request($uri);
        $this->assertEquals($data->query, $rqst->getQuery());
    }

    public function uriProvider()
    {
        return [
            [
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
            ],
            [
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
            ],
            [
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
            ],
            [
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
            ],
            [
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
            ]
        ];
    }

    /**
     * @dataProvider formProvider
     */
    public function testEncodesFormFields($form)
    {
        $rqst = new Request();
        $rqst->setFormFields($form);
        $body = $rqst->getBody();
        parse_str($body, $fields);
        $this->assertEquals($form, $fields);
    }

    /**
     * @dataProvider formProvider
     */
    public function testDecodesFormFields($form)
    {
        $rqst = new Request();
        $rqst->setFormFields($form);
        $fields = $rqst->getFormFields();
        $this->assertEquals($form, $fields);
    }

    public function formProvider()
    {
        $faker = Factory::create();
        return [
            [
                [
                    "firstName" => $faker->firstName,
                    "lastName" => $faker->lastName,
                    "username" => $faker->userName
                ]
            ]
        ];
    }

    /**
     * @dataProvider queryProvider
     */
    public function testSetsQuery($input, $expected)
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
    public function testThrowsExceptionOnInvalidQuery($query)
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
    public function testThrowsExceptionOnInvalidScheme($scheme)
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
    public function testSetsDefaultPort($scheme, $port)
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
     * @preserveGlobalState disabled
     */
    public function testReadsServerRequestMethod($serverVars, $expected)
    {
        $_SERVER = array_merge($_SERVER, $serverVars);
        $rqst = new Request();
        $rqst->readHttpRequest();
        $this->assertEquals($expected->method, $rqst->getMethod());
    }
    /**
     * @dataProvider serverProvider
     * @preserveGlobalState disabled
     */
    public function testReadsServerRequestHost($serverVars, $expected)
    {
        $_SERVER = array_merge($_SERVER, $serverVars);
        $rqst = new Request();
        $rqst->readHttpRequest();
        $this->assertEquals($expected->host, $rqst->getHostname());
    }

    /**
     * @dataProvider serverProvider
     * @preserveGlobalState disabled
     */
    public function testReadsServerRequestPath($serverVars, $expected)
    {
        $_SERVER = array_merge($_SERVER, $serverVars);
        $rqst = new Request();
        $rqst->readHttpRequest();
        $this->assertEquals($expected->path, $rqst->getPath());
    }

    /**
     * @dataProvider serverProvider
     * @preserveGlobalState disabled
     */
    public function testReadsServerRequestHeaders($serverVars, $expected)
    {
        $_SERVER = array_merge($_SERVER, $serverVars);
        $rqst = new Request();
        $rqst->readHttpRequest();
        foreach ($expected->headers as $name => $value) {
            $this->assertEquals($value, $rqst->getHeader($name));
        }
    }

    /**
     * @preserveGlobalState disabled
     */
    public function testReadsStaticRequest()
    {
        $data = $this->serverProvider();
        $serverVars = $data[0][0];
        $expected = $data[0][1];

        $_SERVER = array_merge($_SERVER, $serverVars);
        $rqst = Request::getRequest();
        $this->assertEquals($expected->host, $rqst->getHostname());

        $rqst2 =  Request::getRequest();
        $this->assertSame($rqst2, $rqst);
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
