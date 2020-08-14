<?php

namespace WellRESTed\Routing\Route;

use WellRESTed\Dispatching\Dispatcher;
use WellRESTed\Message\Response;
use WellRESTed\Message\ServerRequest;
use WellRESTed\Test\Doubles\MiddlewareMock;
use WellRESTed\Test\Doubles\NextMock;
use WellRESTed\Test\TestCase;

class MethodMapTest extends TestCase
{
    private $dispatcher;
    private $request;
    private $response;
    private $next;
    private $middleware;

    protected function setUp(): void
    {
        $this->request = new ServerRequest();
        $this->response = new Response();
        $this->next = new NextMock();
        $this->middleware = new MiddlewareMock();
        $this->dispatcher = new Dispatcher();
    }

    private function getMethodMap(): MethodMap
    {
        return new MethodMap($this->dispatcher);
    }

    // -------------------------------------------------------------------------

    public function testDispatchesMiddlewareWithMatchingMethod(): void
    {
        $this->request = $this->request->withMethod('GET');

        $map = $this->getMethodMap();
        $map->register('GET', $this->middleware);
        $map($this->request, $this->response, $this->next);

        $this->assertTrue($this->middleware->called);
    }

    public function testTreatsMethodNamesCaseSensitively(): void
    {
        $this->request = $this->request->withMethod('get');

        $middlewareUpper = new MiddlewareMock();
        $middlewareLower = new MiddlewareMock();

        $map = $this->getMethodMap();
        $map->register('GET', $middlewareUpper);
        $map->register('get', $middlewareLower);
        $map($this->request, $this->response, $this->next);

        $this->assertTrue($middlewareLower->called);
    }

    public function testDispatchesWildcardMiddlewareWithNonMatchingMethod(): void
    {
        $this->request = $this->request->withMethod('GET');

        $map = $this->getMethodMap();
        $map->register('*', $this->middleware);
        $map($this->request, $this->response, $this->next);

        $this->assertTrue($this->middleware->called);
    }

    public function testDispatchesGetMiddlewareForHeadByDefault(): void
    {
        $this->request = $this->request->withMethod('HEAD');

        $map = $this->getMethodMap();
        $map->register('GET', $this->middleware);
        $map($this->request, $this->response, $this->next);

        $this->assertTrue($this->middleware->called);
    }

    public function testRegistersMiddlewareForMultipleMethods(): void
    {
        $map = $this->getMethodMap();
        $map->register('GET,POST', $this->middleware);

        $this->request = $this->request->withMethod('GET');
        $map($this->request, $this->response, $this->next);

        $this->request = $this->request->withMethod('POST');
        $map($this->request, $this->response, $this->next);

        $this->assertEquals(2, $this->middleware->callCount);
    }

    public function testSettingNullUnregistersMiddleware(): void
    {
        $this->request = $this->request->withMethod('POST');

        $map = $this->getMethodMap();
        $map->register('POST', $this->middleware);
        $map->register('POST', null);
        $response = $map($this->request, $this->response, $this->next);

        $this->assertEquals(405, $response->getStatusCode());
    }

    public function testSetsStatusTo200ForOptions(): void
    {
        $this->request = $this->request->withMethod('OPTIONS');

        $map = $this->getMethodMap();
        $map->register('GET', $this->middleware);
        $response = $map($this->request, $this->response, $this->next);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testStopsPropagatingAfterOptions(): void
    {
        $this->request = $this->request->withMethod('OPTIONS');

        $map = $this->getMethodMap();
        $map->register('GET', $this->middleware);
        $map($this->request, $this->response, $this->next);

        $this->assertFalse($this->next->called);
    }

    /**
     * @dataProvider allowedMethodProvider
     * @param string[] $methodsDeclared
     * @param string[] $methodsAllowed
     */
    public function testSetsAllowHeaderForOptions(array $methodsDeclared, array $methodsAllowed): void
    {
        $this->request = $this->request->withMethod('OPTIONS');

        $map = $this->getMethodMap();
        foreach ($methodsDeclared as $method) {
            $map->register($method, $this->middleware);
        }
        $response = $map($this->request, $this->response, $this->next);

        $this->assertContainsEach($methodsAllowed, $response->getHeaderLine('Allow'));
    }

    public function testSetsStatusTo405ForBadMethod(): void
    {
        $this->request = $this->request->withMethod('POST');

        $map = $this->getMethodMap();
        $map->register('GET', $this->middleware);
        $response = $map($this->request, $this->response, $this->next);

        $this->assertEquals(405, $response->getStatusCode());
    }

    public function testStopsPropagatingAfterBadMethod(): void
    {
        $this->request = $this->request->withMethod('POST');

        $map = $this->getMethodMap();
        $map->register('GET', $this->middleware);
        $map($this->request, $this->response, $this->next);
        $this->assertFalse($this->next->called);
    }

    /**
     * @dataProvider allowedMethodProvider
     * @param string[] $methodsDeclared
     * @param string[] $methodsAllowed
     */
    public function testSetsAllowHeaderForBadMethod(array $methodsDeclared, array $methodsAllowed): void
    {
        $this->request = $this->request->withMethod('BAD');

        $map = $this->getMethodMap();
        foreach ($methodsDeclared as $method) {
            $map->register($method, $this->middleware);
        }
        $response = $map($this->request, $this->response, $this->next);

        $this->assertContainsEach($methodsAllowed, $response->getHeaderLine('Allow'));
    }

    public function allowedMethodProvider(): array
    {
        return [
            [['GET'], ['GET', 'HEAD', 'OPTIONS']],
            [['GET', 'POST'], ['GET', 'POST', 'HEAD', 'OPTIONS']],
            [['POST'], ['POST', 'OPTIONS']],
            [['POST'], ['POST', 'OPTIONS']],
            [['GET', 'PUT,DELETE'], ['GET', 'PUT', 'DELETE', 'HEAD', 'OPTIONS']],
        ];
    }

    private function assertContainsEach($expectedList, $actual): void
    {
        foreach ($expectedList as $expected) {
            if (strpos($actual, $expected) === false) {
                $this->assertTrue(false, "'$actual' does not contain expected '$expected'");
            }
        }
        $this->assertTrue(true);
    }
}
