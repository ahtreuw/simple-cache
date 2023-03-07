<?php declare(strict_types=1);

namespace Vulpes\SimpleCache;

use Psr\SimpleCache\CacheInterface;

interface Gateway extends CacheInterface
{
    public function getPrefix(): string;
}
