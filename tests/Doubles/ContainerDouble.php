<?php

namespace WellRESTed\Test\Doubles;

use Psr\Container\ContainerInterface;

/**
 * PSR-11 Dependency Injection Container.
 */
class ContainerDouble implements ContainerInterface
{
    public array $services;

    public function __construct(array $services = [])
    {
        $this->services = $services;
    }

    public function get(string $id)
    {
        return $this->services[$id];
    }

    public function has(string $id)
    {
        return isset($this->services);
    }
}

