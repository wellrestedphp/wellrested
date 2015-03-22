<?php

namespace WellRESTed\Message\Header;

class HeaderCollection implements \ArrayAccess
{
    /**
     * @var array
     *
     * Hash array with keys as lowercase header names, Header[] as values.
     */
    private $headers;

    public function __construct()
    {
        $this->headers = [];
    }

    /**
     * @param string $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->headers[strtolower($offset)]);
    }

    /**
     * @param mixed $offset
     * @return Header[]
     */
    public function offsetGet($offset)
    {
        return $this->headers[strtolower($offset)];
    }

    /**
     * @param string $offset
     * @param string $value
     */
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

    /**
     * @param string $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->headers[strtolower($offset)]);
    }

    /**
     * Make a deep copy of all headers in the arrays
     */
    public function __clone()
    {
        $originalHeaders = $this->headers;
        $this->headers = [];
        foreach ($originalHeaders as $name => $headers) {
            $clonedHeaders = [];
            foreach ($headers as $header) {
                $clonedHeaders[] = clone $header;
            }
            $this->headers[$name] = $clonedHeaders;
        }
    }
}
