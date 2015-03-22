<?php

namespace WellRESTed\Test\Unit\Message;

use WellRESTed\Message\Request;

class RequestTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers WellRESTed\Message\Request::getHeaders
     * @uses WellRESTed\Message\Request::withUri
     * @uses WellRESTed\Message\Request::__clone
     * @uses WellRESTed\Message\Message
     * @uses WellRESTed\Message\HeaderCollection
     */
    public function testGetHeadersReturnsHostFromUri()
    {
        $uri = $this->prophesize("\\Psr\\Http\\Message\\UriInterface");
        $uri->getHost()->willReturn("localhost");

        $request = new Request();
        $request = $request->withUri($uri->reveal());

        $headers = $request->getHeaders();
        $this->assertEquals(["localhost"], $headers["Host"]);
    }

    /**
     * @covers WellRESTed\Message\Request::getHeaders
     * @uses WellRESTed\Message\Request::withUri
     * @uses WellRESTed\Message\Request::__clone
     * @uses WellRESTed\Message\Message
     * @uses WellRESTed\Message\HeaderCollection
     */
    public function testGetHeadersPrefersExplicitHostHeader()
    {
        $uri = $this->prophesize("\\Psr\\Http\\Message\\UriInterface");
        $uri->getHost()->willReturn("localhot");

        $request = new Request();
        $request = $request->withUri($uri->reveal());
        $request = $request->withHeader("Host", "www.mysite.com");

        $headers = $request->getHeaders();
        $this->assertEquals(["www.mysite.com"], $headers["Host"]);
    }

    /**
     * @covers WellRESTed\Message\Request::getHeader
     * @uses WellRESTed\Message\Request::getRequestTarget
     * @uses WellRESTed\Message\Request::withUri
     * @uses WellRESTed\Message\Request::__clone
     * @uses WellRESTed\Message\Message
     * @uses WellRESTed\Message\HeaderCollection
     */
    public function testGetHeaderReturnsHostFromUri()
    {
        $uri = $this->prophesize("\\Psr\\Http\\Message\\UriInterface");
        $uri->getHost()->willReturn("localhost");

        $request = new Request();
        $request = $request->withUri($uri->reveal());
        $this->assertEquals("localhost", $request->getHeader("host"));
    }

    /**
     * @covers WellRESTed\Message\Request::getHeader
     * @uses WellRESTed\Message\Request::getRequestTarget
     * @uses WellRESTed\Message\Request::withUri
     * @uses WellRESTed\Message\Request::__clone
     * @uses WellRESTed\Message\Message
     * @uses WellRESTed\Message\HeaderCollection
     */
    public function testGetHeaderPrefersExplicitHostHeader()
    {
        $uri = $this->prophesize("\\Psr\\Http\\Message\\UriInterface");
        $uri->getHost()->willReturn("localhot");

        $request = new Request();
        $request = $request->withUri($uri->reveal());
        $request = $request->withHeader("Host", "www.mysite.com");
        $this->assertEquals("www.mysite.com", $request->getHeader("host"));
    }

    /**
     * @covers WellRESTed\Message\Request::getHeaderLines
     * @uses WellRESTed\Message\Request::withUri
     * @uses WellRESTed\Message\Request::__clone
     * @uses WellRESTed\Message\Message
     * @uses WellRESTed\Message\HeaderCollection
     */
    public function testGetHeaderLinesReturnsHostFromUri()
    {
        $uri = $this->prophesize("\\Psr\\Http\\Message\\UriInterface");
        $uri->getHost()->willReturn("localhot");

        $request = new Request();
        $request = $request->withUri($uri->reveal());
        $this->assertEquals(["localhot"], $request->getHeaderLines("host"));
    }

    /**
     * @covers WellRESTed\Message\Request::getHeaderLines
     * @uses WellRESTed\Message\Request::withUri
     * @uses WellRESTed\Message\Request::__clone
     * @uses WellRESTed\Message\Message
     * @uses WellRESTed\Message\HeaderCollection
     */
    public function testGetHeaderLinesPrefersExplicitHostHeader()
    {
        $uri = $this->prophesize("\\Psr\\Http\\Message\\UriInterface");
        $uri->getHost()->willReturn("localhot");

        $request = new Request();
        $request = $request->withUri($uri->reveal());
        $request = $request->withHeader("Host", "www.mysite.com");
        $this->assertEquals(["www.mysite.com"], $request->getHeaderLines("host"));
    }

    /**
     * @covers WellRESTed\Message\Request::getRequestTarget
     * @uses WellRESTed\Message\Request::withRequestTarget
     * @uses WellRESTed\Message\Request::__clone
     * @uses WellRESTed\Message\Message
     * @uses WellRESTed\Message\HeaderCollection
     */
    public function testGetRequestTargetPrefersConreteRequestTarget()
    {
        $request = new Request();
        $request = $request->withRequestTarget("*");
        $this->assertEquals("*", $request->getRequestTarget());
    }

    /**
     * @covers WellRESTed\Message\Request::getRequestTarget
     * @uses WellRESTed\Message\Request::withUri
     * @uses WellRESTed\Message\Request::__clone
     * @uses WellRESTed\Message\Message
     * @uses WellRESTed\Message\HeaderCollection
     */
    public function testGetRequestTargetUsesOriginFormOfUri()
    {
        $uri = $this->prophesize("\\Psr\\Http\\Message\\UriInterface");
        $uri->getPath()->willReturn("/my/path");
        $uri->getQuery()->willReturn("cat=Molly&dog=Bear");

        $request = new Request();
        $request = $request->withUri($uri->reveal());
        $this->assertEquals("/my/path?cat=Molly&dog=Bear", $request->getRequestTarget());
    }

    /**
     * @covers WellRESTed\Message\Request::getRequestTarget
     * @uses WellRESTed\Message\Message
     * @uses WellRESTed\Message\HeaderCollection
     */
    public function testGetRequestTargetReturnsSlashByDefault()
    {
        $request = new Request();
        $this->assertEquals("/", $request->getRequestTarget());
    }

    /**
     * @covers WellRESTed\Message\Request::getMethod
     * @uses WellRESTed\Message\Message
     * @uses WellRESTed\Message\HeaderCollection
     */
    public function testGetMethodReturnsGetByDefault()
    {
        $request = new Request();
        $this->assertEquals("GET", $request->getMethod());
    }

    /**
     * @covers WellRESTed\Message\Request::withMethod
     * @covers WellRESTed\Message\Request::getMethod
     * @uses WellRESTed\Message\Request::__clone
     * @uses WellRESTed\Message\Message
     * @uses WellRESTed\Message\HeaderCollection
     */
    public function testWithMethodCreatesNewInstance()
    {
        $request = new Request();
        $request = $request->withMethod("POST");
        $this->assertEquals("POST", $request->getMethod());
    }

    /**
     * @covers WellRESTed\Message\Request::withRequestTarget
     * @covers WellRESTed\Message\Request::getRequestTarget
     * @uses WellRESTed\Message\Request::__clone
     * @uses WellRESTed\Message\Message
     * @uses WellRESTed\Message\HeaderCollection
     */
    public function testWithRequestTargetCreatesNewInstance()
    {
        $request = new Request();
        $request = $request->withRequestTarget("*");
        $this->assertEquals("*", $request->getRequestTarget());
    }

    /**
     * @covers WellRESTed\Message\Request::withUri
     * @covers WellRESTed\Message\Request::getUri
     * @uses WellRESTed\Message\Request::__clone
     * @uses WellRESTed\Message\Message
     * @uses WellRESTed\Message\HeaderCollection
     */
    public function testWithUriCreatesNewInstance()
    {
        $uri = $this->prophesize("\\Psr\\Http\\Message\\UriInterface");
        $uri = $uri->reveal();

        $request = new Request();
        $request = $request->withUri($uri);
        $this->assertSame($uri, $request->getUri());
    }

    /**
     * @covers WellRESTed\Message\Request::__clone
     * @uses WellRESTed\Message\Request::getUri
     * @uses WellRESTed\Message\Request::withUri
     * @uses WellRESTed\Message\Request::getHeader
     * @uses WellRESTed\Message\Request::withHeader
     * @uses WellRESTed\Message\Request::getRequestTarget
     * @uses WellRESTed\Message\Message
     * @uses WellRESTed\Message\HeaderCollection
     */
    public function testWithUriPreservesOriginalRequest()
    {
        $uri1 = $this->prophesize("\\Psr\\Http\\Message\\UriInterface");
        $uri1 = $uri1->reveal();

        $uri2 = $this->prophesize("\\Psr\\Http\\Message\\UriInterface");
        $uri2 = $uri2->reveal();

        $request1 = new Request();
        $request1 = $request1->withUri($uri1);
        $request1 = $request1->withHeader("Accept", "application/json");

        $request2 = $request1->withUri($uri2);
        $request2 = $request2->withHeader("Accept", "text/plain");

        $this->assertEquals($uri1, $request1->getUri());
        $this->assertEquals("application/json", $request1->getHeader("Accept"));

        $this->assertEquals($uri2, $request2->getUri());
        $this->assertEquals("text/plain", $request2->getHeader("Accept"));
    }
}
