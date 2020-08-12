<?php

namespace WellRESTed\Message;

use InvalidArgumentException;
use WellRESTed\Test\TestCase;

class RequestTest extends TestCase
{
    // ------------------------------------------------------------------------
    // Construction

    public function testCreatesInstanceWithNoParameters(): void
    {
        $request = new Request();
        $this->assertNotNull($request);
    }

    public function testCreatesInstanceWithMethod(): void
    {
        $method = 'POST';
        $request = new Request($method);
        $this->assertSame($method, $request->getMethod());
    }

    public function testCreatesInstanceWithUri(): void
    {
        $uri = new Uri();
        $request = new Request('GET', $uri);
        $this->assertSame($uri, $request->getUri());
    }

    public function testCreatesInstanceWithStringUri(): void
    {
        $uri = 'http://localhost:8080';
        $request = new Request('GET', $uri);
        $this->assertSame($uri, (string) $request->getUri());
    }

    public function testSetsHeadersOnConstruction(): void
    {
        $request = new Request('GET', '/', [
            'X-foo' => ['bar', 'baz']
        ]);
        $this->assertEquals(['bar', 'baz'], $request->getHeader('X-foo'));
    }

    public function testSetsBodyOnConstruction(): void
    {
        $body = new NullStream();
        $request = new Request('GET', '/', [], $body);
        $this->assertSame($body, $request->getBody());
    }

    // ------------------------------------------------------------------------
    // Request Target

    public function testGetRequestTargetPrefersExplicitRequestTarget(): void
    {
        $request = new Request();
        $request = $request->withRequestTarget('*');
        $this->assertEquals('*', $request->getRequestTarget());
    }

    public function testGetRequestTargetUsesOriginFormOfUri(): void
    {
        $uri = new Uri('/my/path?cat=Molly&dog=Bear');
        $request = new Request();
        $request = $request->withUri($uri);
        $this->assertEquals('/my/path?cat=Molly&dog=Bear', $request->getRequestTarget());
    }

    public function testGetRequestTargetReturnsSlashByDefault(): void
    {
        $request = new Request();
        $this->assertEquals('/', $request->getRequestTarget());
    }

    public function testWithRequestTargetCreatesNewInstance(): void
    {
        $request = new Request();
        $request = $request->withRequestTarget('*');
        $this->assertEquals('*', $request->getRequestTarget());
    }

    // ------------------------------------------------------------------------
    // Method

    public function testGetMethodReturnsGetByDefault(): void
    {
        $request = new Request();
        $this->assertEquals('GET', $request->getMethod());
    }

    public function testWithMethodCreatesNewInstance(): void
    {
        $request = new Request();
        $request = $request->withMethod('POST');
        $this->assertEquals('POST', $request->getMethod());
    }

    /**
     * @dataProvider invalidMethodProvider
     * @param mixed $method
     */
    public function testWithMethodThrowsExceptionOnInvalidMethod($method): void
    {
        $this->expectException(InvalidArgumentException::class);
        $request = new Request();
        $request->withMethod($method);
    }

    public function invalidMethodProvider(): array
    {
        return [
            [0],
            [false],
            ['WITH SPACE']
        ];
    }

    // ------------------------------------------------------------------------
    // Request URI

    public function testGetUriReturnsEmptyUriByDefault(): void
    {
        $request = new Request();
        $uri = new Uri();
        $this->assertEquals($uri, $request->getUri());
    }

    public function testWithUriCreatesNewInstance(): void
    {
        $uri = new Uri();
        $request = new Request();
        $request = $request->withUri($uri);
        $this->assertSame($uri, $request->getUri());
    }

    public function testWithUriPreservesOriginalRequest(): void
    {
        $uri1 = new Uri();
        $uri2 = new Uri();

        $request1 = new Request();
        $request1 = $request1->withUri($uri1);
        $request1 = $request1->withHeader('Accept', 'application/json');

        $request2 = $request1->withUri($uri2);
        $request2 = $request2->withHeader('Accept', 'text/plain');

        $this->assertNotEquals($request1->getHeader('Accept'), $request2->getHeader('Accept'));
    }

    public function testWithUriUpdatesHostHeader(): void
    {
        $hostname = 'bar.com';
        $uri = new uri("http://$hostname");

        $request = new Request();
        $request = $request->withHeader('Host', 'foo.com');
        $request = $request->withUri($uri);
        $this->assertSame([$hostname], $request->getHeader('Host'));
    }

    public function testWithUriDoesNotUpdatesHostHeaderWhenUriHasNoHost(): void
    {
        $hostname = 'foo.com';
        $uri = new Uri();

        $request = new Request();
        $request = $request->withHeader('Host', $hostname);
        $request = $request->withUri($uri);
        $this->assertSame([$hostname], $request->getHeader('Host'));
    }

    public function testPreserveHostUpdatesHostHeaderWhenHeaderIsOriginallyMissing(): void
    {
        $hostname = 'foo.com';
        $uri = new uri("http://$hostname");

        $request = new Request();
        $request = $request->withUri($uri, true);
        $this->assertSame([$hostname], $request->getHeader('Host'));
    }

    public function testPreserveHostDoesNotUpdatesWhenBothAreMissingHosts(): void
    {
        $uri = new Uri();

        $request = new Request();
        $request = $request->withUri($uri, true);
        $this->assertSame([], $request->getHeader('Host'));
    }

    public function testPreserveHostDoesNotUpdateHostHeader(): void
    {
        $hostname = 'foo.com';
        $uri = new uri('http://bar.com');

        $request = new Request();
        $request = $request->withHeader('Host', $hostname);
        $request = $request->withUri($uri, true);
        $this->assertSame([$hostname], $request->getHeader('Host'));
    }
}
