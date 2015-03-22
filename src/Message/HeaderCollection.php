<?php

namespace WellRESTed\Message;

class HeaderCollection implements \ArrayAccess
{
    /**
     * @var array
     *
     * Hash array mapping lowercase header names to original case header names.
     */
    private $fields;

    /**
     * @var array
     *
     * Hash array mapping lowercase header names to values as string[]
     */
    private $values;

    public function __construct()
    {
        $this->fields = [];
        $this->values = [];
    }

    /**
     * @param string $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->values[strtolower($offset)]);
    }

    /**
     * @param mixed $offset
     * @return string[]
     */
    public function offsetGet($offset)
    {
        return $this->values[strtolower($offset)];
    }

    /**
     * @param string $offset
     * @param string $value
     */
    public function offsetSet($offset, $value)
    {
        $normalized = strtolower($offset);
        $this->fields[$normalized] = $offset;
        if (isset($this->values[$normalized])) {
            $this->values[$normalized][] = $value;
        } else {
            $this->values[$normalized] = [$value];
        }
    }

    /**
     * @param string $offset
     */
    public function offsetUnset($offset)
    {
        $normalized = strtolower($offset);
        unset($this->fields[$normalized]);
        unset($this->values[$normalized]);
    }
}
