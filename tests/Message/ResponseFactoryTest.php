<?php

namespace WellRESTed\Message;

use WellRESTed\Test\TestCase;

class ResponseFactoryTest extends TestCase
{
    public function testCreatesResponseWithStatusCode200ByDefault(): void
    {
        $statusCode = 200;
        $reasonPhrase = 'OK';

        $factory = new ResponseFactory();
        $response = $factory->createResponse();

        $this->assertEquals($statusCode, $response->getStatusCode());
        $this->assertEquals($reasonPhrase, $response->getReasonPhrase());
    }

    public function testCreateResponseWithStatusCode(): void
    {
        $statusCode = 201;
        $reasonPhrase = 'Created';

        $factory = new ResponseFactory();
        $response = $factory->createResponse($statusCode);

        $this->assertEquals($statusCode, $response->getStatusCode());
        $this->assertEquals($reasonPhrase, $response->getReasonPhrase());
    }

    public function testCreateResponseWithStatusCodeAndCustomReasonPhrase(): void
    {
        $statusCode = 512;
        $reasonPhrase = 'Shortage of Chairs';

        $factory = new ResponseFactory();
        $response = $factory->createResponse($statusCode, $reasonPhrase);

        $this->assertEquals($statusCode, $response->getStatusCode());
        $this->assertEquals($reasonPhrase, $response->getReasonPhrase());
    }
}
