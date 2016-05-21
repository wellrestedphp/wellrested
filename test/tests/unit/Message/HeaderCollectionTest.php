<?php

namespace WellRESTed\Test\Message;

use WellRESTed\Message\HeaderCollection;

/**
 * @covers WellRESTed\Message\HeaderCollection
 * @group message
 */
class HeaderCollectionTest extends \PHPUnit_Framework_TestCase
{
    public function testAddsSingleHeaderAndIndicatesCaseInsensitiveIsset()
    {
        $collection = new HeaderCollection();
        $collection["Content-Type"] = "application/json";
        $this->assertTrue(isset($collection["content-type"]));
    }

    public function testAddsMultipleHeadersAndIndicatesCaseInsensitiveIsset()
    {
        $collection = new HeaderCollection();
        $collection["Set-Cookie"] = "cat=Molly";
        $collection["SET-COOKIE"] = "dog=Bear";
        $this->assertTrue(isset($collection["set-cookie"]));
    }

    public function testReturnsHeadersWithCaseInsensitiveHeaderName()
    {
        $collection = new HeaderCollection();
        $collection["Set-Cookie"] = "cat=Molly";
        $collection["SET-COOKIE"] = "dog=Bear";

        $headers = $collection["set-cookie"];
        $this->assertEquals(2, count(array_intersect($headers, ["cat=Molly", "dog=Bear"])));
    }

    public function testRemovesHeadersWithCaseInsensitiveHeaderName()
    {
        $collection = new HeaderCollection();
        $collection["Set-Cookie"] = "cat=Molly";
        $collection["SET-COOKIE"] = "dog=Bear";
        unset($collection["set-cookie"]);
        $this->assertFalse(isset($collection["set-cookie"]));
    }

    /** @coversNothing */
    public function testCloneMakesDeepCopyOfHeaders()
    {
        $collection = new HeaderCollection();
        $collection["Set-Cookie"] = "cat=Molly";

        $clone = clone $collection;
        unset($clone["Set-Cookie"]);

        $this->assertTrue(isset($collection["set-cookie"]) && !isset($clone["set-cookie"]));
    }

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

        $countUnmatched = count(array_diff($expected, $headers)) + count(array_diff($headers, $expected));
        $this->assertEquals(0, $countUnmatched);
    }

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
