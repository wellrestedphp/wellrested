<?php

namespace pjdietz\WellRESTed\Test;

use pjdietz\WellRESTed\Exceptions\HttpExceptions\NotFoundException;
use pjdietz\WellRESTed\Handler;

class HandlerTest extends \PHPUnit_Framework_TestCase
{
    public function testGetResponse()
    {
        $mockRequest = $this->getMock('\pjdietz\WellRESTed\Interfaces\RequestInterface');
        $mockHandler = $this->getMockForAbstractClass('\pjdietz\WellRESTed\Handler');
        /** @var \pjdietz\WellRESTed\Handler $mockHandler */
        $this->assertNotNull($mockHandler->getResponse($mockRequest));
    }

    /**
     * @dataProvider verbProvider
     */
    public function testCallMethodForHttpVerb($verb)
    {
        $mockRequest = $this->getMock('\pjdietz\WellRESTed\Interfaces\RequestInterface');
        $mockRequest->expects($this->any())
            ->method('getMethod')
            ->will($this->returnValue($verb));

        $mockHandler = $this->getMockForAbstractClass('\pjdietz\WellRESTed\Handler');
        /** @var \pjdietz\WellRESTed\Handler $mockHandler */
        $this->assertNotNull($mockHandler->getResponse($mockRequest));
    }

    public function verbProvider()
    {
        return [
            ["GET"],
            ["POST"],
            ["PUT"],
            ["DELETE"],
            ["HEAD"],
            ["PATCH"],
            ["OPTIONS"],
            ["NOTALLOWED"]
        ];
    }

    public function testTranslateHttpExceptionToResponse()
    {
         $mockRequest = $this->getMock('\pjdietz\WellRESTed\Interfaces\RequestInterface');
         $mockRequest->expects($this->any())
             ->method('getMethod')
             ->will($this->returnValue("GET"));

         $handler = new ExceptionHandler();
         $resp = $handler->getResponse($mockRequest);
         $this->assertEquals(404, $resp->getStatusCode());
    }

    public function testReadAllowedMethods()
    {
        $mockRequest = $this->getMock('\pjdietz\WellRESTed\Interfaces\RequestInterface');
        $mockRequest->expects($this->any())
            ->method('getMethod')
            ->will($this->returnValue("OPTIONS"));

        $handler = new OptionsHandler();

        $resp = $handler->getResponse($mockRequest);
        $this->assertEquals("GET, POST", $resp->getHeader("Allow"));
    }

}

class OptionsHandler extends Handler
{
    protected function getAllowedMethods()
    {
        return ["GET","POST"];
    }
}

class ExceptionHandler extends Handler
{
    protected function get()
    {
        throw new NotFoundException();
    }
}
