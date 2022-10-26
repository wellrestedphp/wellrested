<?php

declare(strict_types=1);

namespace WellRESTed\Routing;

enum TrailingSlashMode
{
    case Strict;
    case Loose;
    case Redirect;
}
