<?php

namespace WellRESTed\Test\Unit\Message;

use WellRESTed\Message\Request;
use WellRESTed\Message\Uri;

/**
 * @coversDefaultClass WellRESTed\Message\Request
 * @uses WellRESTed\Message\Request
 * @uses WellRESTed\Message\Request
 * @uses WellRESTed\Message\Message
 * @uses WellRESTed\Message\HeaderCollection
 * @uses WellRESTed\Message\Uri
 * @group message
 */
class RequestTest extends \PHPUnit_Framework_TestCase
{
    // ------------------------------------------------------------------------
    // Construction

    /**
     * @covers ::__construct
     */
    public function testCreatesInstance()
    {
        $request = new Request();
        $this->assertNotNull($request);
    }

    /**
     * @covers ::__construct
     */
    public function testCreatesInstanceWithUri()
    {
        $uri = $this->prophesize('Psr\Http\Message\UriInterface');
        $uri = $uri->reveal();
        $request = new Request($uri);
        $this->assertSame($uri, $request->getUri());
    }

    /**
     * @covers ::__construct
     */
    public function testCreatesInstanceWithMethod()
    {
        $method = "POST";
        $request = new Request(null, $method);
        $this->assertSame($method, $request->getMethod());
    }

    /**
     * @covers ::__construct
     */
    public function testSetsHeadersOnConstruction()
    {
        $request = new Request(null, null, [
            "X-foo" => ["bar","baz"]
        ]);
        $this->assertEquals(["bar","baz"], $request->getHeader("X-foo"));
    }

    /**
     * @covers ::__construct
     */
    public function testSetsBodyOnConstruction()
    {
        $body = $this->prophesize('\Psr\Http\Message\StreamInterface');
        $request = new Request(null, null, [], $body->reveal());
        $this->assertSame($body->reveal(), $request->getBody());
    }

    // ------------------------------------------------------------------------
    // Request Target

    /**
     * @covers ::getRequestTarget
     */
    public function testGetRequestTargetPrefersExplicitRequestTarget()
    {
        $request = new Request();
        $request = $request->withRequestTarget("*");
        $this->assertEquals("*", $request->getRequestTarget());
    }

    /**
     * @covers ::getRequestTarget
     */
    public function testGetRequestTargetUsesOriginFormOfUri()
    {
        $uri = $this->prophesize('\Psr\Http\Message\UriInterface');
        $uri->getHost()->willReturn("");
        $uri->getPath()->willReturn("/my/path");
        $uri->getQuery()->willReturn("cat=Molly&dog=Bear");

        $request = new Request();
        $request = $request->withUri($uri->reveal());
        $this->assertEquals("/my/path?cat=Molly&dog=Bear", $request->getRequestTarget());
    }

    /**
     * @covers ::getRequestTarget
     */
    public function testGetRequestTargetReturnsSlashByDefault()
    {
        $request = new Request();
        $this->assertEquals("/", $request->getRequestTarget());
    }

    /**
     * @covers ::withRequestTarget
     * @covers ::getRequestTarget
     */
    public function testWithRequestTargetCreatesNewInstance()
    {
        $request = new Request();
        $request = $request->withRequestTarget("*");
        $this->assertEquals("*", $request->getRequestTarget());
    }

    // ------------------------------------------------------------------------
    // Method

    /**
     * @covers ::getMethod
     */
    public function testGetMethodReturnsGetByDefault()
    {
        $request = new Request();
        $this->assertEquals("GET", $request->getMethod());
    }

    /**
     * @covers ::withMethod
     * @covers ::getValidatedMethod
     * @covers ::getMethod
     */
    public function testWithMethodCreatesNewInstance()
    {
        $request = new Request();
        $request = $request->withMethod("POST");
        $this->assertEquals("POST", $request->getMethod());
    }

    /**
     * @covers ::withMethod
     * @covers ::getValidatedMethod
     * @dataProvider invalidMethodProvider
     * @expectedException \InvalidArgumentException
     */
    public function testWithMethodThrowsExceptionOnInvalidMethod($method)
    {
        $request = new Request();
        $request->withMethod($method);
    }

    public function invalidMethodProvider()
    {
        return [
            [0],
            [false],
            ["WITH SPACE"]
        ];
    }

    // ------------------------------------------------------------------------
    // Request URI

    /**
     * @covers ::getUri
     */
    public function testGetUriReturnsEmptyUriByDefault()
    {
        $request = new Request();
        $uri = new Uri();
        $this->assertEquals($uri, $request->getUri());
    }

    /**
     * @covers ::withUri
     * @covers ::getUri
     */
    public function testWithUriCreatesNewInstance()
    {
        $uri = $this->prophesize('\Psr\Http\Message\UriInterface');
        $uri = $uri->reveal();

        $request = new Request();
        $request = $request->withUri($uri);
        $this->assertSame($uri, $request->getUri());
    }

    /**
     * @covers ::__clone
     */
    public function testWithUriPreservesOriginalRequest()
    {
        $uri1 = $this->prophesize('\Psr\Http\Message\UriInterface');
        $uri1 = $uri1->reveal();

        $uri2 = $this->prophesize('\Psr\Http\Message\UriInterface');
        $uri2 = $uri2->reveal();

        $request1 = new Request();
        $request1 = $request1->withUri($uri1);
        $request1 = $request1->withHeader("Accept", "application/json");

        $request2 = $request1->withUri($uri2);
        $request2 = $request2->withHeader("Accept", "text/plain");

        $this->assertNotEquals($request1->getHeader("Accept"), $request2->getHeader("Accept"));
    }

    /**
     * @covers ::withUri
     */
    public function testWithUriUpdatesHostHeader()
    {
        $hostname = "bar.com";
        $uri = $this->prophesize('\Psr\Http\Message\UriInterface');
        $uri->getHost()->willReturn($hostname);

        $request = new Request();
        $request = $request->withHeader("Host", "foo.com");
        $request = $request->withUri($uri->reveal());
        $this->assertSame([$hostname], $request->getHeader("Host"));
    }

    /**
     * @covers ::withUri
     */
    public function testWithUriDoesNotUpdatesHostHeaderWhenUriHasNoHost()
    {
        $hostname = "foo.com";
        $uri = $this->prophesize('\Psr\Http\Message\UriInterface');
        $uri->getHost()->willReturn("");

        $request = new Request();
        $request = $request->withHeader("Host", $hostname);
        $request = $request->withUri($uri->reveal());
        $this->assertSame([$hostname], $request->getHeader("Host"));
    }

    /**
     * @covers ::withUri
     */
    public function testPreserveHostUpdatesHostHeaderWhenHeaderIsOriginallyMissing()
    {
        $hostname = "foo.com";
        $uri = $this->prophesize('\Psr\Http\Message\UriInterface');
        $uri->getHost()->willReturn($hostname);

        $request = new Request();
        $request = $request->withUri($uri->reveal(), true);
        $this->assertSame([$hostname], $request->getHeader("Host"));
    }

    /**
     * @covers ::withUri
     */
    public function testPreserveHostDoesNotUpdatesWhenBothAreMissingHosts()
    {
        $uri = $this->prophesize('\Psr\Http\Message\UriInterface');
        $uri->getHost()->willReturn("");

        $request = new Request();
        $request = $request->withUri($uri->reveal(), true);
        $this->assertSame([], $request->getHeader("Host"));
    }

    /**
     * @covers ::withUri
     */
    public function testPreserveHostDoesNotUpdateHostHeader()
    {
        $hostname = "foo.com";
        $uri = $this->prophesize('\Psr\Http\Message\UriInterface');
        $uri->getHost()->willReturn("bar.com");

        $request = new Request();
        $request = $request->withHeader("Host", $hostname);
        $request = $request->withUri($uri->reveal(), true);
        $this->assertSame([$hostname], $request->getHeader("Host"));
    }
}
