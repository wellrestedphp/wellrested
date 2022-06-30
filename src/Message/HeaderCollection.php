<?php

namespace WellRESTed\Message;

use ArrayAccess;
use Iterator;

/**
 * HeaderCollection provides case-insensitive access to lists of header values.
 *
 * HeaderCollection preserves the cases of keys as they are set, but treats key
 * access case insensitively.
 *
 * Any values added to HeaderCollection are added to list arrays. Subsequent
 * calls to add a value for a given key will append the new value to the list
 * array of values for that key.
 *
 * @internal This class is an internal class used by Message and is not intended
 *   for direct use by consumers.
 */
class HeaderCollection implements ArrayAccess, Iterator
{
    /**
     * Hash array mapping lowercase header names to original case header names.
     *
     * @var array<string, string>
     */
    private $fields = [];

    /**
     * Hash array mapping lowercase header names to values as string[]
     *
     * @var array<string, string[]>
     */
    private $values = [];

    /**
     * List array of lowercase header names.
     *
     * @var string[]
     */
    private $keys = [];

    /** @var int */
    private $position = 0;

    // -------------------------------------------------------------------------
    // ArrayAccess

    /**
     * @param string $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return isset($this->values[strtolower($offset)]);
    }

    /**
     * @param mixed $offset
     * @return string[]
     */
    public function offsetGet($offset): array
    {
        return $this->values[strtolower($offset)];
    }

    /**
     * @param string $offset
     * @param string $value
     */
    public function offsetSet($offset, $value): void
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
    public function offsetUnset($offset): void
    {
        $normalized = strtolower($offset);
        unset($this->fields[$normalized]);
        unset($this->values[$normalized]);
        // Remove and normalize the list of keys.
        if (($key = array_search($normalized, $this->keys)) !== false) {
            unset($this->keys[$key]);
            $this->keys = array_values($this->keys);
        }
    }

    // -------------------------------------------------------------------------
    // Iterator

    /**
     * @return string[]
     */
    public function current(): array
    {
        return $this->values[$this->keys[$this->position]];
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function key(): string
    {
        return $this->fields[$this->keys[$this->position]];
    }

    public function valid(): bool
    {
        return isset($this->keys[$this->position]);
    }

    public function rewind(): void
    {
        $this->position = 0;
    }
}
