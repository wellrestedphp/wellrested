<?php

namespace WellRESTed\Test\Unit\Message;

use WellRESTed\Message\Request;

/**
 * @uses WellRESTed\Message\Request
 * @uses WellRESTed\Message\Request
 * @uses WellRESTed\Message\Message
 * @uses WellRESTed\Message\HeaderCollection
 */
class RequestTest extends \PHPUnit_Framework_TestCase
{
    // ------------------------------------------------------------------------
    // Request Target

    /**
     * @covers WellRESTed\Message\Request::getRequestTarget
     */
    public function testGetRequestTargetPrefersConreteRequestTarget()
    {
        $request = new Request();
        $request = $request->withRequestTarget("*");
        $this->assertEquals("*", $request->getRequestTarget());
    }

    /**
     * @covers WellRESTed\Message\Request::getRequestTarget
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
     * @covers WellRESTed\Message\Request::getRequestTarget
     */
    public function testGetRequestTargetReturnsSlashByDefault()
    {
        $request = new Request();
        $this->assertEquals("/", $request->getRequestTarget());
    }

    /**
     * @covers WellRESTed\Message\Request::withRequestTarget
     * @covers WellRESTed\Message\Request::getRequestTarget
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
     * @covers WellRESTed\Message\Request::getMethod
     */
    public function testGetMethodReturnsGetByDefault()
    {
        $request = new Request();
        $this->assertEquals("GET", $request->getMethod());
    }

    /**
     * @covers WellRESTed\Message\Request::withMethod
     * @covers WellRESTed\Message\Request::getMethod
     */
    public function testWithMethodCreatesNewInstance()
    {
        $request = new Request();
        $request = $request->withMethod("POST");
        $this->assertEquals("POST", $request->getMethod());
    }

    // ------------------------------------------------------------------------
    // Request URI

    /**
     * @covers WellRESTed\Message\Request::withUri
     * @covers WellRESTed\Message\Request::getUri
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
     * @covers WellRESTed\Message\Request::__clone
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

        $this->assertEquals($uri1, $request1->getUri());
        $this->assertEquals(["application/json"], $request1->getHeader("Accept"));

        $this->assertEquals($uri2, $request2->getUri());
        $this->assertEquals(["text/plain"], $request2->getHeader("Accept"));
    }

    /**
     * @covers WellRESTed\Message\Request::withUri
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
     * @covers WellRESTed\Message\Request::withUri
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
     * @covers WellRESTed\Message\Request::withUri
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
     * @covers WellRESTed\Message\Request::withUri
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
     * @covers WellRESTed\Message\Request::withUri
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
