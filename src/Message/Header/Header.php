<?php

namespace WellRESTed\Message\Header;

/**
 * Represents an HTTP Header
 */
class Header
{
    private $name;
    private $value;

    public function __construct($name, $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    /**
     * @return string Header line as name: value
     */
    public function __toString()
    {
        return $this->getHeaderLine();
    }

    /**
     * @return string Original header name with case preserved
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string Header value
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return string Header line as name: value
     */
    public function getHeaderLine()
    {
        return $this->name . ": " . $this->value;
    }
}
