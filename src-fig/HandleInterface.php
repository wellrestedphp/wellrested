<?php

namespace Psr\Http\ServerMiddleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface HandleInterface
{
    public function __invoke(ServerRequestInterface $request): ResponseInterface;
}

