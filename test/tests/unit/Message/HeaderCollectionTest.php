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

    /**
     * @covers WellRESTed\Message\HeaderCollection::current
     * @covers WellRESTed\Message\HeaderCollection::next
     * @covers WellRESTed\Message\HeaderCollection::key
     * @covers WellRESTed\Message\HeaderCollection::valid
     * @covers WellRESTed\Message\HeaderCollection::rewind
     * @uses WellRESTed\Message\HeaderCollection::__construct
     * @uses WellRESTed\Message\HeaderCollection::offsetSet
     * @uses WellRESTed\Message\HeaderCollection::offsetExists
     * @uses WellRESTed\Message\HeaderCollection::offsetUnset
     */
    public function testIteratesWithOriginalKeys()
    {
        $collection = new HeaderCollection();
        $collection["Content-length"] = "100";
        $collection["Set-Cookie"] = "cat=Molly";
        $collection["Set-Cookie"] = "dog=Bear";
        $collection["Content-type"] = "application/json";
        unset($collection["Content-length"]);

        $headers = [];

        foreach ($collection as $key => $values) {
            $headers[] = $key;
        }

        $expected = ["Content-type", "Set-Cookie"];
        $this->assertEquals(0, count(array_diff($expected, $headers)));
        $this->assertEquals(0, count(array_diff($headers, $expected)));
    }

    /**
     * @covers WellRESTed\Message\HeaderCollection::current
     * @covers WellRESTed\Message\HeaderCollection::next
     * @covers WellRESTed\Message\HeaderCollection::key
     * @covers WellRESTed\Message\HeaderCollection::valid
     * @covers WellRESTed\Message\HeaderCollection::rewind
     * @uses WellRESTed\Message\HeaderCollection::__construct
     * @uses WellRESTed\Message\HeaderCollection::offsetSet
     * @uses WellRESTed\Message\HeaderCollection::offsetExists
     * @uses WellRESTed\Message\HeaderCollection::offsetUnset
     */
    public function testIteratesWithOriginalKeysAndValues()
    {
        $collection = new HeaderCollection();
        $collection["Content-length"] = "100";
        $collection["Set-Cookie"] = "cat=Molly";
        $collection["Set-Cookie"] = "dog=Bear";
        $collection["Content-type"] = "application/json";
        unset($collection["Content-length"]);

        $headers = [];

        foreach ($collection as $key => $values) {
            foreach ($values as $value) {
                if (isset($headers[$key])) {
                    $headers[$key][] = $value;
                } else {
                    $headers[$key] = [$value];
                }
            }
        }

        $expected = [
            "Set-Cookie" => ["cat=Molly", "dog=Bear"],
            "Content-type" => ["application/json"]
        ];

        $this->assertEquals($expected, $headers);
    }
}
