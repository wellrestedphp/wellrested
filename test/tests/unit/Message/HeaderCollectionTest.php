<?php

namespace WellRESTed\Test\Message;

use WellRESTed\Message\HeaderCollection;

class HeaderCollectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers WellRESTed\Message\HeaderCollection::__construct
     */
    public function testCreatesInstance()
    {
        $collection = new HeaderCollection();
        $this->assertNotNull($collection);
    }

    /**
     * @covers WellRESTed\Message\HeaderCollection::offsetSet
     * @covers WellRESTed\Message\HeaderCollection::offsetExists
     * @uses WellRESTed\Message\HeaderCollection::__construct
     */
    public function testAddsSingleHeaderAndIndicatesCaseInsensitiveIsset()
    {
        $collection = new HeaderCollection();
        $collection["Content-Type"] = "application/json";
        $this->assertTrue(isset($collection["content-type"]));
    }

    /**
     * @covers WellRESTed\Message\HeaderCollection::offsetSet
     * @covers WellRESTed\Message\HeaderCollection::offsetExists
     * @uses WellRESTed\Message\HeaderCollection::__construct
     */
    public function testAddsMultipleHeadersAndIndicatesCaseInsensitiveIsset()
    {
        $collection = new HeaderCollection();
        $collection["Set-Cookie"] = "cat=Molly";
        $collection["SET-COOKIE"] = "dog=Bear";
        $this->assertTrue(isset($collection["set-cookie"]));
    }

    /**
     * @covers WellRESTed\Message\HeaderCollection::offsetGet
     * @uses WellRESTed\Message\HeaderCollection::offsetSet
     * @uses WellRESTed\Message\HeaderCollection::__construct
     */
    public function testReturnsHeadersWithCaseInsensitiveHeaderName()
    {
        $collection = new HeaderCollection();
        $collection["Set-Cookie"] = "cat=Molly";
        $collection["SET-COOKIE"] = "dog=Bear";

        $headers = $collection["set-cookie"];
        $this->assertContains("cat=Molly", $headers);
        $this->assertContains("dog=Bear", $headers);
    }

    /**
     * @covers WellRESTed\Message\HeaderCollection::offsetUnset
     * @uses WellRESTed\Message\HeaderCollection::__construct
     * @uses WellRESTed\Message\HeaderCollection::offsetSet
     * @uses WellRESTed\Message\HeaderCollection::offsetExists
     */
    public function testRemovesHeadersWithCaseInsensitiveHeaderName()
    {
        $collection = new HeaderCollection();
        $collection["Set-Cookie"] = "cat=Molly";
        $collection["SET-COOKIE"] = "dog=Bear";
        unset($collection["set-cookie"]);
        $this->assertFalse(isset($collection["set-cookie"]));
    }

    /**
     * @uses WellRESTed\Message\HeaderCollection::__construct
     * @uses WellRESTed\Message\HeaderCollection::offsetSet
     * @uses WellRESTed\Message\HeaderCollection::offsetExists
     * @uses WellRESTed\Message\HeaderCollection::offsetUnset
     */
    public function testCloneMakesDeepCopyOfHeaders()
    {
        $collection = new HeaderCollection();
        $collection["Set-Cookie"] = "cat=Molly";

        $clone = clone $collection;
        unset($clone["Set-Cookie"]);

        $this->assertTrue(isset($collection["set-cookie"]));
        $this->assertFalse(isset($clone["set-cookie"]));
    }
}
