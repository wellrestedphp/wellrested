<?php

namespace WellRESTed\Message;

use InvalidArgumentException;
use WellRESTed\Test\TestCase;

class UriTest extends TestCase
{
    // ------------------------------------------------------------------------
    // Scheme

    public function testDefaultSchemeIsEmpty(): void
    {
        $uri = new Uri();
        $this->assertSame('', $uri->getScheme());
    }

    /** @dataProvider schemeProvider */
    public function testSetsSchemeCaseInsensitively($expected, $scheme): void
    {
        $uri = new Uri();
        $uri = $uri->withScheme($scheme);
        $this->assertSame($expected, $uri->getScheme());
    }

    public function schemeProvider(): array
    {
        return [
            ['http', 'http'],
            ['https', 'https'],
            ['http', 'HTTP'],
            ['https', 'HTTPS'],
            ['', null],
            ['', '']
        ];
    }

    public function testInvalidSchemeThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $uri = new Uri();
        $uri->withScheme('gopher');
    }

    // ------------------------------------------------------------------------
    // Authority

    public function testDefaultAuthorityIsEmpty(): void
    {
        $uri = new Uri();
        $this->assertSame('', $uri->getAuthority());
    }

    public function testRespectsMyAuthoritah(): void
    {
        $this->assertTrue(true);
    }

    /**
     * @dataProvider authorityProvider
     * @param string $expected
     * @param array $components
     */
    public function testConcatenatesAuthorityFromHostAndUserInfo(
        string $expected,
        array $components
    ): void {
        $uri = new Uri();

        if (isset($components['scheme'])) {
            $uri = $uri->withScheme($components['scheme']);
        }

        if (isset($components['user'])) {
            $user = $components['user'];
            $password = null;
            if (isset($components['password'])) {
                $password = $components['password'];
            }
            $uri = $uri->withUserInfo($user, $password);
        }

        if (isset($components['host'])) {
            $uri = $uri->withHost($components['host']);
        }

        if (isset($components['port'])) {
            $uri = $uri->withPort($components['port']);
        }

        $this->assertEquals($expected, $uri->getAuthority());
    }

    public function authorityProvider()
    {
        return [
            [
                'localhost',
                [
                    'host' => 'localhost'
                ]
            ],
            [
                'user@localhost',
                [
                    'host' => 'localhost',
                    'user' => 'user'
                ]
            ],
            [
                'user:password@localhost',
                [
                    'host' => 'localhost',
                    'user' => 'user',
                    'password' => 'password'
                ]
            ],
            [
                'localhost',
                [
                    'host' => 'localhost',
                    'password' => 'password'
                ]
            ],
            [
                'localhost',
                [
                    'scheme' => 'http',
                    'host' => 'localhost',
                    'port' => 80
                ]
            ],
            [
                'localhost',
                [
                    'scheme' => 'https',
                    'host' => 'localhost',
                    'port' => 443
                ]
            ],
            [
                'localhost:4430',
                [
                    'scheme' => 'https',
                    'host' => 'localhost',
                    'port' => 4430
                ]
            ],
            [
                'localhost:8080',
                [
                    'scheme' => 'http',
                    'host' => 'localhost',
                    'port' => 8080
                ]
            ],
            [
                'user:password@localhost:4430',
                [
                    'scheme' => 'https',
                    'user' => 'user',
                    'password' => 'password',
                    'host' => 'localhost',
                    'port' => 4430
                ]
            ],
        ];
    }

    // ------------------------------------------------------------------------
    // User Info

    public function testDefaultUserInfoIsEmpty(): void
    {
        $uri = new Uri();
        $this->assertSame('', $uri->getUserInfo());
    }

    /**
     * @dataProvider userInfoProvider
     *
     * @param string $expected The combined user:password value
     * @param string $user The username to set
     * @param string|null $password The password to set
     */
    public function testSetsUserInfo(string $expected, string $user, ?string $password): void
    {
        $uri = new Uri();
        $uri = $uri->withUserInfo($user, $password);
        $this->assertSame($expected, $uri->getUserInfo());
    }

    public function userInfoProvider(): array
    {
        return [
            ['user:password', 'user', 'password'],
            ['user', 'user', ''],
            ['user', 'user', null],
            ['', '', 'password'],
            ['', '', '']
        ];
    }

    // ------------------------------------------------------------------------
    // Host

    public function testDefaultHostIsEmpty(): void
    {
        $uri = new Uri();
        $this->assertSame('', $uri->getHost());
    }

    /**
     * @dataProvider hostProvider
     * @param string $expected
     * @param string $host
     */
    public function testSetsHost(string $expected, string $host): void
    {
        $uri = new Uri();
        $uri = $uri->withHost($host);
        $this->assertSame($expected, $uri->getHost());
    }

    public function hostProvider(): array
    {
        return [
            ['', ''],
            ['localhost', 'localhost'],
            ['localhost', 'LOCALHOST'],
            ['foo.com', 'FOO.com']
        ];
    }

    /**
     * @dataProvider invalidHostProvider
     * @param mixed $host
     */
    public function testInvalidHostThrowsException($host): void
    {
        $this->expectException(InvalidArgumentException::class);
        $uri = new Uri();
        $uri->withHost($host);
    }

    public function invalidHostProvider(): array
    {
        return [
            [null],
            [false],
            [0]
        ];
    }

    // ------------------------------------------------------------------------
    // Port

    public function testDefaultPortWithNoSchemeIsNull(): void
    {
        $uri = new Uri();
        $this->assertNull($uri->getPort());
    }

    public function testDefaultPortForHttpSchemeIs80(): void
    {
        $uri = new Uri();
        $this->assertSame(80, $uri->withScheme('http')->getPort());
    }

    public function testDefaultPortForHttpsSchemeIs443(): void
    {
        $uri = new Uri();
        $this->assertSame(443, $uri->withScheme('https')->getPort());
    }

    /**
     * @dataProvider portAndSchemeProvider
     * @param mixed $expectedPort
     * @param mixed $scheme
     * @param mixed $port
     */
    public function testReturnsPortWithSchemeDefaults($expectedPort, $scheme, $port): void
    {
        $uri = new Uri();
        $uri = $uri->withScheme($scheme)->withPort($port);
        $this->assertSame($expectedPort, $uri->getPort());
    }

    public function portAndSchemeProvider(): array
    {
        return [
            [null, '', null],
            [80, 'http', null],
            [443, 'https', null],
            [8080, '', 8080],
            [8080, 'http', '8080'],
            [8080, 'https', 8080.0]
        ];
    }

    /**
     * @dataProvider invalidPortProvider
     * @param mixed $port
     */
    public function testInvalidPortThrowsException($port): void
    {
        $this->expectException(InvalidArgumentException::class);
        $uri = new Uri();
        $uri->withPort($port);
    }

    public function invalidPortProvider(): array
    {
        return [
            [true],
            [-1],
            [65536],
            ['dog']
        ];
    }

    // ------------------------------------------------------------------------
    // Path

    public function testDefaultPathIsEmpty(): void
    {
        $uri = new Uri();
        $this->assertSame('', $uri->getPath());
    }

    /**
     * @dataProvider pathProvider
     * @param string $expected
     * @param string $path
     */
    public function testSetsEncodedPath(string $expected, string $path): void
    {
        $uri = new Uri();
        $uri = $uri->withPath($path);
        $this->assertSame($expected, $uri->getPath());
    }

    /**
     * @dataProvider pathProvider
     * @param string $expected
     * @param string $path
     */
    public function testDoesNotDoubleEncodePath(string $expected, string $path): void
    {
        $uri = new Uri();
        $uri = $uri->withPath($path);
        $uri = $uri->withPath($uri->getPath());
        $this->assertSame($expected, $uri->getPath());
    }

    public function pathProvider()
    {
        return [
            ['', ''],
            ['/', '/'],
            ['*', '*'],
            ['/my/path', '/my/path'],
            ['/encoded%2Fslash', '/encoded%2Fslash'],
            ['/percent/%25', '/percent/%'],
            ['/%C3%A1%C3%A9%C3%AD%C3%B3%C3%BA', '/áéíóú']
        ];
    }

    // ------------------------------------------------------------------------
    // Query

    public function testDefaultQueryIsEmpty(): void
    {
        $uri = new Uri();
        $this->assertSame('', $uri->getQuery());
    }

    /**
     * @dataProvider queryProvider
     * @param string $expected
     * @param string $query
     */
    public function testSetsEncodedQuery(string $expected, string $query): void
    {
        $uri = new Uri();
        $uri = $uri->withQuery($query);
        $this->assertSame($expected, $uri->getQuery());
    }

    /**
     * @dataProvider queryProvider
     * @param string $expected
     * @param string $query
     */
    public function testDoesNotDoubleEncodeQuery(string $expected, string $query): void
    {
        $uri = new Uri();
        $uri = $uri->withQuery($query);
        $uri = $uri->withQuery($uri->getQuery());
        $this->assertSame($expected, $uri->getQuery());
    }

    public function queryProvider(): array
    {
        return [
            ['cat=molly', 'cat=molly'],
            ['cat=molly&dog=bear', 'cat=molly&dog=bear'],
            ['accents=%C3%A1%C3%A9%C3%AD%C3%B3%C3%BA', 'accents=áéíóú']
        ];
    }

    /**
     * @dataProvider invalidPathProvider
     * @param mixed $path
     */
    public function testInvalidPathThrowsException($path): void
    {
        $this->expectException(InvalidArgumentException::class);
        $uri = new Uri();
        $uri->withPath($path);
    }

    public function invalidPathProvider(): array
    {
        return [
            [null],
            [false],
            [0]
        ];
    }

    // ------------------------------------------------------------------------
    // Fragment

    public function testDefaultFragmentIsEmpty(): void
    {
        $uri = new Uri();
        $this->assertSame('', $uri->getFragment());
    }

    /**
     * @dataProvider fragmentProvider
     * @param string $expected
     * @param string|null $fragment
     */
    public function testSetsEncodedFragment(string $expected, ?string $fragment): void
    {
        $uri = new Uri();
        $uri = $uri->withFragment($fragment);
        $this->assertSame($expected, $uri->getFragment());
    }

    /**
     * @dataProvider fragmentProvider
     * @param string $expected
     * @param string|null $fragment
     */
    public function testDoesNotDoubleEncodeFragment(string $expected, ?string $fragment): void
    {
        $uri = new Uri();
        $uri = $uri->withFragment($fragment);
        $uri = $uri->withFragment($uri->getFragment());
        $this->assertSame($expected, $uri->getFragment());
    }

    public function fragmentProvider(): array
    {
        return [
            ['', null],
            ['molly', 'molly'],
            ['%C3%A1%C3%A9%C3%AD%C3%B3%C3%BA', 'áéíóú']
        ];
    }

    // ------------------------------------------------------------------------
    // Concatenation

    /**
     * @dataProvider componentProvider
     * @param string $expected
     * @param array $components
     */
    public function testConcatenatesComponents(string $expected, array $components): void
    {
        $uri = new Uri();

        if (isset($components['scheme'])) {
            $uri = $uri->withScheme($components['scheme']);
        }

        if (isset($components['user'])) {
            $user = $components['user'];
            $password = null;
            if (isset($components['password'])) {
                $password = $components['password'];
            }
            $uri = $uri->withUserInfo($user, $password);
        }

        if (isset($components['host'])) {
            $uri = $uri->withHost($components['host']);
        }

        if (isset($components['port'])) {
            $uri = $uri->withPort($components['port']);
        }

        if (isset($components['path'])) {
            $uri = $uri->withPath($components['path']);
        }

        if (isset($components['query'])) {
            $uri = $uri->withQuery($components['query']);
        }

        if (isset($components['fragment'])) {
            $uri = $uri->withFragment($components['fragment']);
        }

        $this->assertEquals($expected, (string) $uri);
    }

    public function componentProvider()
    {
        return [
            [
                'http://localhost/path',
                [
                    'scheme' => 'http',
                    'host' => 'localhost',
                    'path' => '/path'
                ]
            ],
            [
                '//localhost/path',
                [
                    'host' => 'localhost',
                    'path' => '/path'
                ]
            ],
            [
                '/path',
                [
                    'path' => '/path'
                ]
            ],
            [
                '/path?cat=molly&dog=bear',
                [
                    'path' => '/path',
                    'query' => 'cat=molly&dog=bear'
                ]
            ],
            [
                '/path?cat=molly&dog=bear#fragment',
                [
                    'path' => '/path',
                    'query' => 'cat=molly&dog=bear',
                    'fragment' => 'fragment'
                ]
            ],
            [
                'https://user:password@localhost:4430/path?cat=molly&dog=bear#fragment',
                [
                    'scheme' => 'https',
                    'user' => 'user',
                    'password' => 'password',
                    'host' => 'localhost',
                    'port' => 4430,
                    'path' => '/path',
                    'query' => 'cat=molly&dog=bear',
                    'fragment' => 'fragment'
                ]
            ],
            // Asterisk Form
            [
                '*',
                [
                    'path' => '*'
                ]
            ],
        ];
    }

    /**
     * @dataProvider stringUriProvider
     * @param string $expected
     * @param string $input
     */
    public function testUriCreatedFromStringNormalizesString(string $expected, string $input): void
    {
        $uri = new Uri($input);
        $this->assertSame($expected, (string) $uri);
    }

    public function stringUriProvider(): array
    {
        return [
            [
                'http://localhost/path',
                'http://localhost:80/path'
            ],
            [
                'https://localhost/path',
                'https://localhost:443/path'
            ],
            [
                'https://my.sub.sub.domain.com/path',
                'https://my.sub.sub.domain.com/path'
            ],
            [
                'https://user:password@localhost:4430/path?cat=molly&dog=bear#fragment',
                'https://user:password@localhost:4430/path?cat=molly&dog=bear#fragment'
            ],
            [
                '/path',
                '/path'
            ],
            [
                '//double/slash',
                '//double/slash'
            ],
            [
                'no/slash',
                'no/slash'
            ],
            [
                '*',
                '*'
            ]
        ];
    }
}
