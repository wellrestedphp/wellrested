<?php

namespace WellRESTed\Message;

use WellRESTed\Test\TestCase;

class HeaderCollectionTest extends TestCase
{
    public function testAddsSingleHeaderAndIndicatesCaseInsensitiveIsset(): void
    {
        $collection = new HeaderCollection();
        $collection['Content-Type'] = 'application/json';
        $this->assertTrue(isset($collection['content-type']));
    }

    public function testAddsMultipleHeadersAndIndicatesCaseInsensitiveIsset(): void
    {
        $collection = new HeaderCollection();
        $collection['Set-Cookie'] = 'cat=Molly';
        $collection['SET-COOKIE'] = 'dog=Bear';
        $this->assertTrue(isset($collection['set-cookie']));
    }

    public function testReturnsHeadersWithCaseInsensitiveHeaderName(): void
    {
        $collection = new HeaderCollection();
        $collection['Set-Cookie'] = 'cat=Molly';
        $collection['SET-COOKIE'] = 'dog=Bear';

        $headers = $collection['set-cookie'];
        $matched = array_intersect($headers, ['cat=Molly', 'dog=Bear']);
        $this->assertCount(2, $matched);
    }

    public function testRemovesHeadersWithCaseInsensitiveHeaderName(): void
    {
        $collection = new HeaderCollection();
        $collection['Set-Cookie'] = 'cat=Molly';
        $collection['SET-COOKIE'] = 'dog=Bear';
        unset($collection['set-cookie']);
        $this->assertFalse(isset($collection['set-cookie']));
    }

    public function testCloneMakesDeepCopyOfHeaders(): void
    {
        $collection = new HeaderCollection();
        $collection['Set-Cookie'] = 'cat=Molly';

        $clone = clone $collection;
        unset($clone['Set-Cookie']);

        $this->assertTrue(isset($collection['set-cookie']) && !isset($clone['set-cookie']));
    }

    public function testIteratesWithOriginalKeys(): void
    {
        $collection = new HeaderCollection();
        $collection['Content-length'] = '100';
        $collection['Set-Cookie'] = 'cat=Molly';
        $collection['Set-Cookie'] = 'dog=Bear';
        $collection['Content-type'] = 'application/json';
        unset($collection['Content-length']);

        $headers = [];

        foreach ($collection as $key => $values) {
            $headers[] = $key;
        }

        $expected = ['Content-type', 'Set-Cookie'];

        $countUnmatched = count(array_diff($expected, $headers)) + count(array_diff($headers, $expected));
        $this->assertEquals(0, $countUnmatched);
    }

    public function testIteratesWithOriginalKeysAndValues(): void
    {
        $collection = new HeaderCollection();
        $collection['Content-length'] = '100';
        $collection['Set-Cookie'] = 'cat=Molly';
        $collection['Set-Cookie'] = 'dog=Bear';
        $collection['Content-type'] = 'application/json';
        unset($collection['Content-length']);

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
            'Set-Cookie' => ['cat=Molly', 'dog=Bear'],
            'Content-type' => ['application/json']
        ];

        $this->assertEquals($expected, $headers);
    }
}
