<?php

namespace pjdietz\WellRESTed\Test;

use pjdietz\WellRESTed\HandlerUnpacker;
use pjdietz\WellRESTed\Interfaces\HandlerInterface;
use pjdietz\WellRESTed\Interfaces\RequestInterface;

/**
 * @covers pjdietz\WellRESTed\HandlerUnpacker
 */
class HandlerUnpackerTest extends \PHPUnit_Framework_TestCase
{
    public function testUnpacksFromCallable()
    {
        $handlerContainer = function () {
            return new HandlerUnpackerTest_Handler();
        };
        $handlerUnpacker = new HandlerUnpacker();
        $handler = $handlerUnpacker->unpack($handlerContainer);
        $this->assertInstanceOf("\\pjdietz\\WellRESTed\\Interfaces\\HandlerInterface", $handler);
    }

    public function testUnpacksFromString()
    {
        $handlerContainer = __NAMESPACE__ . "\\HandlerUnpackerTest_Handler";
        $handlerUnpacker = new HandlerUnpacker();
        $handler = $handlerUnpacker->unpack($handlerContainer);
        $this->assertInstanceOf("\\pjdietz\\WellRESTed\\Interfaces\\HandlerInterface", $handler);
    }

    public function testUnpacksInstance()
    {
        $handler = new HandlerUnpackerTest_Handler();
        $handlerUnpacker = new HandlerUnpacker();
        $handler = $handlerUnpacker->unpack($handler);
        $this->assertInstanceOf("\\pjdietz\\WellRESTed\\Interfaces\\HandlerInterface", $handler);
    }

    /**
     * @expectedException \UnexpectedValueException
     */
    public function testThrowsExceptionWhenUnpackedInstanceDoesNotImplementInterface()
    {
        $handlerUnpacker = new HandlerUnpacker();
        $handlerUnpacker->unpack("\\stdClass");
    }
}

class HandlerUnpackerTest_Handler implements HandlerInterface
{
    public function getResponse(RequestInterface $request, array $args = null)
    {
        return null;
    }
}
