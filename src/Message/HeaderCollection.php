<?php

namespace WellRESTed\Message;

use ArrayAccess;
use Iterator;

/**
 * HeaderCollection provides case-insenstive access to lists of header values.
 *
 * This class is an internal class used by Message and is not intended for
 * direct use by consumers.
 *
 * HeaderCollection preserves the cases of keys as they are set, but treats key
 * access case insesitively.
 *
 * Any values added to HeaderCollection are added to list arrays. Subsequent
 * calls to add a value for a given key will append the new value to the list
 * array of values for that key.
 */
class HeaderCollection implements ArrayAccess, Iterator
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

    /**
     * @var string[]
     *
     * List arrary of lowercase header names.
     */
    private $keys;

    /** @var int */
    private $position = 0;

    public function __construct()
    {
        $this->keys = [];
        $this->fields = [];
        $this->values = [];
    }

    // ------------------------------------------------------------------------
    // ArrayAccess

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

        // Add the normalized key to the list of keys, if not already set.
        if (!in_array($normalized, $this->keys)) {
            $this->keys[] = $normalized;
        }

        // Add or update the preserved case key.
        $this->fields[$normalized] = $offset;

        // Store the value.
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
        // Remove and renormalize the list of keys.
        if (($key = array_search($normalized, $this->keys)) !== false) {
            unset($this->keys[$key]);
            $this->keys = array_values($this->keys);
        }
    }

    // ------------------------------------------------------------------------
    // Iterator

    public function current()
    {
        return $this->values[$this->keys[$this->position]];
    }

    public function next()
    {
        ++$this->position;
    }

    public function key()
    {
        return $this->fields[$this->keys[$this->position]];
    }

    public function valid()
    {
        return isset($this->keys[$this->position]);
    }

    public function rewind()
    {
        $this->position = 0;
    }
}
