<?php declare(strict_types=1);

namespace Vulpes\SimpleCache;

use DateInterval;
use Psr\SimpleCache\CacheInterface;

class NullCache implements CacheInterface
{
    use SimpleCacheTrait;

    public function get(string $key, mixed $default = null): mixed
    {
        return $default;
    }

    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        return false;
    }

    public function delete(string $key): bool
    {
        return false;
    }

    public function clear(): bool
    {
        return false;
    }

    public function has(string $key): bool
    {
        return false;
    }
}
