<?php

namespace WellRESTed\Test\Message;

use WellRESTed\Message\HeaderCollection;

/**
 * @coversDefaultClass WellRESTed\Message\HeaderCollection
 * @uses WellRESTed\Message\HeaderCollection
 * @group message
 */
class HeaderCollectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::__construct
     */
    public function testCreatesInstance()
    {
        $collection = new HeaderCollection();
        $this->assertNotNull($collection);
    }

    /**
     * @covers ::offsetSet
     * @covers ::offsetExists
     */
    public function testAddsSingleHeaderAndIndicatesCaseInsensitiveIsset()
    {
        $collection = new HeaderCollection();
        $collection["Content-Type"] = "application/json";
        $this->assertTrue(isset($collection["content-type"]));
    }

    /**
     * @covers ::offsetSet
     * @covers ::offsetExists
     */
    public function testAddsMultipleHeadersAndIndicatesCaseInsensitiveIsset()
    {
        $collection = new HeaderCollection();
        $collection["Set-Cookie"] = "cat=Molly";
        $collection["SET-COOKIE"] = "dog=Bear";
        $this->assertTrue(isset($collection["set-cookie"]));
    }

    /**
     * @covers ::offsetGet
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
     * @covers ::offsetUnset
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
     * @coversNothing
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
     * @covers ::current
     * @covers ::next
     * @covers ::key
     * @covers ::valid
     * @covers ::rewind
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
     * @covers ::current
     * @covers ::next
     * @covers ::key
     * @covers ::valid
     * @covers ::rewind
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
