<?php

declare(strict_types=1);

namespace App\Domain\Model;

enum PackingSource: string
{
    case Api = 'api';
    case Fallback = 'fallback';
    case Cache = 'cache';
}
