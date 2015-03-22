<?php

namespace pjdietz\WellRESTed\Test\Message\Header;

use WellRESTed\Message\Header\HeaderCollection;

class HeaderCollectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers WellRESTed\Message\Header\HeaderCollection::__construct
     */
    public function testCreatesInstance()
    {
        $collection = new HeaderCollection();
        $this->assertNotNull($collection);
    }

    /**
     * @covers WellRESTed\Message\Header\HeaderCollection::offsetSet
     * @covers WellRESTed\Message\Header\HeaderCollection::offsetExists
     * @uses WellRESTed\Message\Header\HeaderCollection::__construct
     * @uses WellRESTed\Message\Header\Header
     */
    public function testAddsSingleHeaderAndIndicatesCaseInsensitiveIsset()
    {
        $collection = new HeaderCollection();
        $collection["Content-Type"] = "application/json";
        $this->assertTrue(isset($collection["content-type"]));
    }

    /**
     * @covers WellRESTed\Message\Header\HeaderCollection::offsetSet
     * @covers WellRESTed\Message\Header\HeaderCollection::offsetExists
     * @uses WellRESTed\Message\Header\HeaderCollection::__construct
     * @uses WellRESTed\Message\Header\Header
     */
    public function testAddsMultipleHeadersAndIndicatesCaseInsensitiveIsset()
    {
        $collection = new HeaderCollection();
        $collection["Set-Cookie"] = "cat=Molly";
        $collection["SET-COOKIE"] = "dog=Bear";
        $this->assertTrue(isset($collection["set-cookie"]));
    }

    /**
     * @covers WellRESTed\Message\Header\HeaderCollection::offsetGet
     * @uses WellRESTed\Message\Header\HeaderCollection::offsetSet
     * @uses WellRESTed\Message\Header\HeaderCollection::__construct
     * @uses WellRESTed\Message\Header\Header
     */
    public function testReturnsHeadersWithCaseInsensitiveHeaderName()
    {
        $collection = new HeaderCollection();
        $collection["Set-Cookie"] = "cat=Molly";
        $collection["SET-COOKIE"] = "dog=Bear";

        $headers = $collection["set-cookie"];
        $this->assertContains("Set-Cookie: cat=Molly", $headers);
        $this->assertContains("SET-COOKIE: dog=Bear", $headers);
    }

    /**
     * @covers WellRESTed\Message\Header\HeaderCollection::offsetUnset
     * @uses WellRESTed\Message\Header\HeaderCollection::__construct
     * @uses WellRESTed\Message\Header\HeaderCollection::offsetSet
     * @uses WellRESTed\Message\Header\HeaderCollection::offsetExists
     * @uses WellRESTed\Message\Header\Header
     */
    public function testRemovesHeadersWithCaseInsensitiveHeaderName()
    {
        $collection = new HeaderCollection();
        $collection["Set-Cookie"] = "cat=Molly";
        $collection["SET-COOKIE"] = "dog=Bear";
        unset($collection["set-cookie"]);
        $this->assertFalse(isset($collection["set-cookie"]));
    }
}
