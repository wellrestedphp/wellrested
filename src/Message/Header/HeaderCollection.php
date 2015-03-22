<?php

namespace WellRESTed\Message\Header;

class HeaderCollection implements \ArrayAccess
{
    private $headers;

    public function __construct()
    {
        $this->headers = [];
    }

    public function offsetExists($offset)
    {
        return isset($this->headers[strtolower($offset)]);
    }

    public function offsetGet($offset)
    {
        return $this->headers[strtolower($offset)];
    }

    public function offsetSet($offset, $value)
    {
        $header = new Header($offset, $value);
        $normalized = strtolower($offset);
        if (isset($this->headers[$normalized])) {
            $this->headers[$normalized][] = $header;
        } else {
            $this->headers[$normalized] = [$header];
        }
    }

    public function offsetUnset($offset)
    {
        unset($this->headers[strtolower($offset)]);
    }
}
