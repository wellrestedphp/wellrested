<?php

declare(strict_types=1);

namespace WellRESTed;

use RuntimeException;
use WeakReference;

trait ServerReferenceTrait
{
    /** @var WeakReference<Server> */
    private WeakReference $server;

    private function setServer(Server $server): void
    {
        $this->server = WeakReference::create($server);
    }

    private function getServer(): Server
    {
        return $this->server->get()
            ?? throw new RuntimeException('Server was deallocated.');
    }
}
