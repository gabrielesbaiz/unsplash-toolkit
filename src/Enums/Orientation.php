<?php

declare(strict_types=1);

namespace Gabrielesbaiz\UnsplashToolkit\Enums;

enum Orientation: string
{
    case Landscape = 'landscape';
    case Portrait = 'portrait';
    case Squarish = 'squarish';
}
