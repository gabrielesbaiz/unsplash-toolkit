<?php

declare(strict_types=1);

namespace Gabrielesbaiz\UnsplashToolkit\Enums;

enum OrderBy: string
{
    case Latest = 'latest';
    case Oldest = 'oldest';
    case Popular = 'popular';
    case Views = 'views';
    case Downloads = 'downloads';
    case Relevant = 'relevant';
}
