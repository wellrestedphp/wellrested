<?php

namespace WellRESTed\Test\Message\Header;

use WellRESTed\Message\Header\Header;

class HeaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers WellRESTed\Message\Header\Header::__construct
     */
    public function testCreatesInstance()
    {
        $header = new Header("Content-Type", "application/json");
        $this->assertNotNull($header);
    }

    /**
     * @covers WellRESTed\Message\Header\Header::getName
     * @uses   WellRESTed\Message\Header\Header::__construct
     */
    public function testReturnsName()
    {
        $header = new Header("Content-Type", "application/json");
        $this->assertEquals("Content-Type", $header->getName());
    }

    /**
     * @covers WellRESTed\Message\Header\Header::getValue
     * @uses   WellRESTed\Message\Header\Header::__construct
     */
    public function testReturnsValue()
    {
        $header = new Header("Content-Type", "application/json");
        $this->assertEquals("application/json", $header->getValue());
    }

    /**
     * @covers WellRESTed\Message\Header\Header::getHeaderLine
     * @uses   WellRESTed\Message\Header\Header::__construct
     */
    public function testReturnsHeaderLine()
    {
        $header = new Header("Content-Type", "application/json");
        $this->assertEquals("Content-Type: application/json", $header->getHeaderLine());
    }

    /**
     * @covers WellRESTed\Message\Header\Header::__toString
     * @uses   WellRESTed\Message\Header\Header::__construct
     * @uses   WellRESTed\Message\Header\Header::getHeaderLine
     */
    public function testToStringReturnsHeaderLine()
    {
        $header = new Header("Content-Type", "application/json");
        $this->assertEquals("Content-Type: application/json", (string) $header);
    }
}
