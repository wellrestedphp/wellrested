<?php

declare(strict_types=1);

namespace WellRESTed\Routing;

enum TrailingSlashMode
{
    case STRICT;
    case LOOSE;
    case REDIRECT;
}
