<?php

namespace WellRESTed;

use Psr\Container\ContainerInterface;

class Configuration
{
    private ?ContainerInterface $contaner = null;

    public function getContainer(): ?ContainerInterface
    {
        return $this->contaner;
    }

    public function setContainer(?ContainerInterface $container): self
    {
        $this->contaner = $container;
        return $this;
    }
}
