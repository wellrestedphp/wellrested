<?php

namespace WellRESTed\Test\Message;

use WellRESTed\Message\Uri;

/**
 * @uses WellRESTed\Message\Uri
 */
class UriTest extends \PHPUnit_Framework_TestCase
{
    // ------------------------------------------------------------------------
    // Scheme

    /**
     * @covers WellRESTed\Message\Uri::getScheme
     */
    public function testDefaultSchemeIsEmpty()
    {
        $uri = new Uri();
        $this->assertSame("", $uri->getScheme());
    }

    /**
     * @covers WellRESTed\Message\Uri::withScheme
     * @dataProvider schemeProvider
     * @param string $expected The expected result of getScheme
     * @param string $scheme The scheme to pass to withScheme
     */
    public function testSetsSchemeCaseInsensitively($expected, $scheme)
    {
        $uri = new Uri();
        $uri = $uri->withScheme($scheme);
        $this->assertSame($expected, $uri->getScheme());
    }

    public function schemeProvider()
    {
        return [
            ["http", "http"],
            ["https", "https"],
            ["http", "HTTP"],
            ["https", "HTTPS"],
            ["", null],
            ["", ""]
        ];
    }

    /**
     * @covers WellRESTed\Message\Uri::withScheme
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidSchemeThrowsException()
    {
        $uri = new Uri();
        $uri->withScheme("gopher");
    }

    // ------------------------------------------------------------------------
    // Authority

    /**
     * @covers WellRESTed\Message\Uri::getAuthority
     */
    public function testDefaultAuthorityIsEmpty()
    {
        $uri = new Uri();
        $this->assertSame("", $uri->getAuthority());
    }

    public function testRespectsMyAuthoritai()
    {
        $this->assertTrue(true);
    }

    /**
     * @covers WellRESTed\Message\Uri::getAuthority
     * @dataProvider authorityProvider
     * @param string $expected
     * @param array $components
     */
    public function testConcatenatesAuthorityFromHostAndUserInfo($expected, $components)
    {
        $uri = new Uri();

        if (isset($components["scheme"])) {
            $uri = $uri->withScheme($components["scheme"]);
        }

        if (isset($components["user"])) {
            $user = $components["user"];
            $password = null;
            if (isset($components["password"])) {
                $password = $components["password"];
            }
            $uri = $uri->withUserInfo($user, $password);
        }

        if (isset($components["host"])) {
            $uri = $uri->withHost($components["host"]);
        }

        if (isset($components["port"])) {
            $uri = $uri->withPort($components["port"]);
        }

        $this->assertEquals($expected, $uri->getAuthority());
    }

    public function authorityProvider()
    {
        return [
            [
                "localhost",
                [
                    "host" => "localhost"
                ]
            ],
            [
                "user@localhost",
                [
                    "host" => "localhost",
                    "user" => "user"
                ]
            ],
            [
                "user:password@localhost",
                [
                    "host" => "localhost",
                    "user" => "user",
                    "password" => "password"
                ]
            ],
            [
                "localhost",
                [
                    "host" => "localhost",
                    "password" => "password"
                ]
            ],
            [
                "localhost",
                [
                    "scheme" => "http",
                    "host" => "localhost",
                    "port" => 80
                ]
            ],
            [
                "localhost",
                [
                    "scheme" => "https",
                    "host" => "localhost",
                    "port" => 443
                ]
            ],
            [
                "localhost:4430",
                [
                    "scheme" => "https",
                    "host" => "localhost",
                    "port" => 4430
                ]
            ],
            [
                "localhost:8080",
                [
                    "scheme" => "http",
                    "host" => "localhost",
                    "port" => 8080
                ]
            ],
            [
                "user:password@localhost:4430",
                [
                    "scheme" => "https",
                    "user" => "user",
                    "password" => "password",
                    "host" => "localhost",
                    "port" => 4430
                ]
            ],
        ];
    }

    // ------------------------------------------------------------------------
    // User Info

    /**
     * @covers WellRESTed\Message\Uri::getUserInfo
     */
    public function testDefaultUserInfoIsEmpty()
    {
        $uri = new Uri();
        $this->assertSame("", $uri->getUserInfo());
    }

    /**
     * @covers WellRESTed\Message\Uri::getUserInfo
     * @covers WellRESTed\Message\Uri::withUserInfo
     * @dataProvider userInfoProvider
     *
     * @param string $expected The combined user:password value
     * @param string $user The username to set
     * @param string $password The password to set
     */
    public function testSetsUserInfo($expected, $user, $password)
    {
        $uri = new Uri();
        $uri = $uri->withUserInfo($user, $password);
        $this->assertSame($expected, $uri->getUserInfo());
    }

    public function userInfoProvider()
    {
        return [
            ["user:password", "user", "password"],
            ["user", "user", ""],
            ["user", "user", null],
            ["", "", "password"],
            ["", "", ""]
        ];
    }

    // ------------------------------------------------------------------------
    // Host

    /**
     * @covers WellRESTed\Message\Uri::getHost
     */
    public function testDefaultHostIsEmpty()
    {
        $uri = new Uri();
        $this->assertSame("", $uri->getHost());
    }

    /**
     * @covers WellRESTed\Message\Uri::getHost
     * @covers WellRESTed\Message\Uri::withHost
     * @dataProvider hostProvider
     * @param $expected
     * @param $host
     */
    public function testSetsHost($expected, $host)
    {
        $uri = new Uri();
        $uri = $uri->withHost($host);
        $this->assertSame($expected, $uri->getHost());
    }

    public function hostProvider()
    {
        return [
            ["", ""],
            ["localhost", "localhost"],
            ["localhost", "LOCALHOST"],
            ["foo.com", "FOO.com"]
        ];
    }

    /**
     * @covers WellRESTed\Message\Uri::withHost
     * @expectedException \InvalidArgumentException
     * @dataProvider invalidHostProvider
     * @param $host
     */
    public function testInvalidHostThrowsException($host)
    {
        $uri = new Uri();
        $uri->withHost($host);
    }

    public function invalidHostProvider()
    {
        return [
            [null],
            [false],
            [0]
        ];
    }

    // ------------------------------------------------------------------------
    // Port

    /**
     * @covers WellRESTed\Message\Uri::getPort
     */
    public function testDefaultPortWithNoSchemeIsNull()
    {
        $uri = new Uri();
        $this->assertNull($uri->getPort());
    }

    /**
     * @covers WellRESTed\Message\Uri::getPort
     */
    public function testDefaultPortForHttpSchemeIs80()
    {
        $uri = new Uri();
        $this->assertSame(80, $uri->withScheme("http")->getPort());
    }

    /**
     * @covers WellRESTed\Message\Uri::getPort
     */
    public function testDefaultPortForHttpsSchemeIs443()
    {
        $uri = new Uri();
        $this->assertSame(443, $uri->withScheme("https")->getPort());
    }

    /**
     * @covers WellRESTed\Message\Uri::getPort
     * @covers WellRESTed\Message\Uri::withPort
     * @dataProvider portAndSchemeProvider
     *
     * @param int|null $expectedPort
     * @param string $scheme
     * @param int|null $port
     */
    public function testReturnsPortWithSchemeDefaults($expectedPort, $scheme, $port)
    {
        $uri = new Uri();
        $uri = $uri->withScheme($scheme)->withPort($port);
        $this->assertSame($expectedPort, $uri->getPort());
    }

    public function portAndSchemeProvider()
    {
        return [
            [null, "", null],
            [80, "http", null],
            [443, "https", null],
            [8080, "", 8080],
            [8080, "http", "8080"],
            [8080, "https", 8080.0]
        ];
    }

    /**
     * @covers WellRESTed\Message\Uri::withPort
     * @expectedException \InvalidArgumentException
     * @dataProvider invalidPortProvider
     * @param int $port
     */
    public function testInvalidPortThrowsException($port)
    {
        $uri = new Uri();
        $uri->withPort($port);
    }

    public function invalidPortProvider()
    {
        return [
            [true],
            [-1],
            [65536],
            ["dog"]
        ];
    }

    // ------------------------------------------------------------------------
    // Path

    /**
     * @covers WellRESTed\Message\Uri::getPath
     */
    public function testDefaultPathIsEmpty()
    {
        $uri = new Uri();
        $this->assertSame("", $uri->getPath());
    }

    /**
     * @covers WellRESTed\Message\Uri::getPath
     * @covers WellRESTed\Message\Uri::withPath
     * @covers WellRESTed\Message\Uri::percentEncode
     * @dataProvider pathProvider
     * @param $expected
     * @param $path
     */
    public function testSetsEncodedPath($expected, $path)
    {
        $uri = new Uri();
        $uri = $uri->withPath($path);
        $this->assertSame($expected, $uri->getPath());
    }

    /**
     * @covers WellRESTed\Message\Uri::getPath
     * @covers WellRESTed\Message\Uri::withPath
     * @covers WellRESTed\Message\Uri::percentEncode
     * @dataProvider pathProvider
     * @param $expected
     * @param $path
     */
    public function testDoesNotDoubleEncodePath($expected, $path)
    {
        $uri = new Uri();
        $uri = $uri->withPath($path);
        $uri = $uri->withPath($uri->getPath());
        $this->assertSame($expected, $uri->getPath());
    }

    public function pathProvider()
    {
        return [
            ["", ""],
            ["/", "/"],
            ["*", "*"],
            ["/my/path", "/my/path"],
            ["/encoded%2Fslash", "/encoded%2Fslash"],
            ["/percent/%25", "/percent/%"],
            ["/%C3%A1%C3%A9%C3%AD%C3%B3%C3%BA", "/áéíóú"]
        ];
    }

    // ------------------------------------------------------------------------
    // Query

    /**
     * @covers WellRESTed\Message\Uri::getQuery
     */
    public function testDefaultQueryIsEmpty()
    {
        $uri = new Uri();
        $this->assertSame("", $uri->getQuery());
    }

    /**
     * @covers WellRESTed\Message\Uri::getQuery
     * @covers WellRESTed\Message\Uri::withQuery
     * @covers WellRESTed\Message\Uri::percentEncode
     * @dataProvider queryProvider
     * @param $expected
     * @param $query
     */
    public function testSetsEncodedQuery($expected, $query)
    {
        $uri = new Uri();
        $uri = $uri->withQuery($query);
        $this->assertSame($expected, $uri->getQuery());
    }

    /**
     * @covers WellRESTed\Message\Uri::getQuery
     * @covers WellRESTed\Message\Uri::withQuery
     * @covers WellRESTed\Message\Uri::percentEncode
     * @dataProvider queryProvider
     * @param $expected
     * @param $query
     */
    public function testDoesNotDoubleEncodeQuery($expected, $query)
    {
        $uri = new Uri();
        $uri = $uri->withQuery($query);
        $uri = $uri->withQuery($uri->getQuery($query));
        $this->assertSame($expected, $uri->getQuery());
    }

    public function queryProvider()
    {
        return [
            ["cat=molly", "cat=molly"],
            ["cat=molly&dog=bear", "cat=molly&dog=bear"],
            ["accents=%C3%A1%C3%A9%C3%AD%C3%B3%C3%BA", "accents=áéíóú"]
        ];
    }

    /**
     * @covers WellRESTed\Message\Uri::withPath
     * @expectedException \InvalidArgumentException
     * @dataProvider invalidPathProvider
     * @param $path
     */
    public function testInvalidPathThrowsException($path)
    {
        $uri = new Uri();
        $uri->withPath($path);
    }

    public function invalidPathProvider()
    {
        return [
            [null],
            [false],
            [0]
        ];
    }

    // ------------------------------------------------------------------------
    // Fragment

    /**
     * @covers WellRESTed\Message\Uri::getFragment
     */
    public function testDefaultFragmentIsEmpty()
    {
        $uri = new Uri();
        $this->assertSame("", $uri->getFragment());
    }

    /**
     * @covers WellRESTed\Message\Uri::getFragment
     * @covers WellRESTed\Message\Uri::withFragment
     * @covers WellRESTed\Message\Uri::percentEncode
     * @dataProvider fragmentProvider
     * @param $expected
     * @param $fragment
     */
    public function testSetsEncodedFragment($expected, $fragment)
    {
        $uri = new Uri();
        $uri = $uri->withFragment($fragment);
        $this->assertSame($expected, $uri->getFragment());
    }

    /**
     * @covers WellRESTed\Message\Uri::getFragment
     * @covers WellRESTed\Message\Uri::withFragment
     * @covers WellRESTed\Message\Uri::percentEncode
     * @dataProvider fragmentProvider
     * @param $expected
     * @param $fragment
     */
    public function testDoesNotDoubleEncodeFragment($expected, $fragment)
    {
        $uri = new Uri();
        $uri = $uri->withFragment($fragment);
        $uri = $uri->withFragment($uri->getFragment($fragment));
        $this->assertSame($expected, $uri->getFragment());
    }

    public function fragmentProvider()
    {
        return [
            ["", null],
            ["molly", "molly"],
            ["%C3%A1%C3%A9%C3%AD%C3%B3%C3%BA", "áéíóú"]
        ];
    }

    // ------------------------------------------------------------------------
    // Concatenation

    /**
     * @covers WellRESTed\Message\Uri::__toString
     * @dataProvider componentProvider
     * @param string $expected
     * @param array $components
     */
    public function testConcatenatesComponents($expected, $components)
    {
        $uri = new Uri();

        if (isset($components["scheme"])) {
            $uri = $uri->withScheme($components["scheme"]);
        }

        if (isset($components["user"])) {
            $user = $components["user"];
            $password = null;
            if (isset($components["password"])) {
                $password = $components["password"];
            }
            $uri = $uri->withUserInfo($user, $password);
        }

        if (isset($components["host"])) {
            $uri = $uri->withHost($components["host"]);
        }

        if (isset($components["port"])) {
            $uri = $uri->withPort($components["port"]);
        }

        if (isset($components["path"])) {
            $uri = $uri->withPath($components["path"]);
        }

        if (isset($components["query"])) {
            $uri = $uri->withQuery($components["query"]);
        }

        if (isset($components["fragment"])) {
            $uri = $uri->withFragment($components["fragment"]);
        }

        $this->assertEquals($expected, (string) $uri);
    }

    public function componentProvider()
    {
        return [
            [
                "http://localhost/path",
                [
                    "scheme" => "http",
                    "host" => "localhost",
                    "path" => "/path"
                ]
            ],
            [
                "//localhost/path",
                [
                    "host" => "localhost",
                    "path" => "/path"
                ]
            ],
            [
                "/path",
                [
                    "path" => "/path"
                ]
            ],
            [
                "/path?cat=molly&dog=bear",
                [
                    "path" => "/path",
                    "query" => "cat=molly&dog=bear"
                ]
            ],
            [
                "/path?cat=molly&dog=bear#fragment",
                [
                    "path" => "/path",
                    "query" => "cat=molly&dog=bear",
                    "fragment" => "fragment"
                ]
            ],
            [
                "https://user:password@localhost:4430/path?cat=molly&dog=bear#fragment",
                [
                    "scheme" => "https",
                    "user" => "user",
                    "password" => "password",
                    "host" => "localhost",
                    "port" => 4430,
                    "path" => "/path",
                    "query" => "cat=molly&dog=bear",
                    "fragment" => "fragment"
                ]
            ],
            // Asterisk Form
            [
                "*",
                [
                    "path" => "*"
                ]
            ],
        ];
    }

    /**
     * @covers WellRESTed\Message\Uri::__construct()
     * @covers WellRESTed\Message\Uri::__toString()
     * @dataProvider stringUriProvider
     */
    public function testUriCreatedFromStringNormalizesString($expected, $input)
    {
        $uri = new Uri($input);
        $this->assertSame($expected, (string) $uri);
    }

    public function stringUriProvider()
    {
        return [
            [
                "http://localhost/path",
                "http://localhost:80/path"
            ],
            [
                "https://localhost/path",
                "https://localhost:443/path"
            ],
            [
                "https://my.sub.sub.domain.com/path",
                "https://my.sub.sub.domain.com/path"
            ],
            [
                "https://user:password@localhost:4430/path?cat=molly&dog=bear#fragment",
                "https://user:password@localhost:4430/path?cat=molly&dog=bear#fragment"
            ],
            [
                "/path",
                "/path"
            ],
            [
                "//double/slash",
                "//double/slash"
            ],
            [
                "no/slash",
                "no/slash"
            ],
            [
                "*",
                "*"
            ]
        ];
    }
}
