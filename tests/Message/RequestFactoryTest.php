<?php

namespace WellRESTed\Message;

use WellRESTed\Test\TestCase;

class RequestFactoryTest extends TestCase
{
    public function testCreatesRequestDELETE(): void
    {
        $method = 'DELETE';
        $uri = 'http://localhost:8080';

        $factory = new RequestFactory();
        $request = $factory->createRequest($method, $uri);

        $this->assertEquals($method, $request->getMethod());
        $this->assertEquals($uri, $request->getUri());
    }

    public function testCreatesRequestGET(): void
    {
        $method = 'GET';
        $uri = 'http://localhost:8080';

        $factory = new RequestFactory();
        $request = $factory->createRequest($method, $uri);

        $this->assertEquals($method, $request->getMethod());
        $this->assertEquals($uri, $request->getUri());
    }

    public function testCreatesRequestPOST(): void
    {
        $method = 'POST';
        $uri = 'http://localhost:8080';

        $factory = new RequestFactory();
        $request = $factory->createRequest($method, $uri);

        $this->assertEquals($method, $request->getMethod());
        $this->assertEquals($uri, $request->getUri());
    }
}