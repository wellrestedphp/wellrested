<?php

namespace WellRESTed;

use Psr\Container\ContainerInterface;

class Configuration
{
    private ?ContainerInterface $contaner = null;

    private ?string $pathVariablesAttributeName = null;

    public function getContainer(): ?ContainerInterface
    {
        return $this->contaner;
    }

    public function setContainer(?ContainerInterface $container): self
    {
        $this->contaner = $container;
        return $this;
    }

    public function getPathVariablesAttributeName(): ?string
    {
        return $this->pathVariablesAttributeName;
    }

    public function setPathVariablesAttributeName(?string $name): self
    {
        $this->pathVariablesAttributeName = $name;
        return $this;
    }
}
