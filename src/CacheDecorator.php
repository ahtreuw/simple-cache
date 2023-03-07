<?php declare(strict_types=1);

namespace Vulpes\SimpleCache;

use DateInterval;
use Psr\SimpleCache\CacheInterface;

class CacheDecorator implements CacheInterface
{
    use SimpleCacheTrait;

    public function __construct(
        private CacheInterface        $gateway,
        private string                $prefix = '',
        private DateInterval|int|null $defaultTtl = null
    ) {}

    public function get(string $key, mixed $default = null): mixed
    {
        if($this->has($key)){
            return $this->gateway->get($this->prefix . $key, $default);
        }
        return $default;
    }

    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        return $this->gateway->set($this->prefix . $key, $value, $ttl ?: $this->defaultTtl);
    }

    public function delete(string $key): bool
    {
        return $this->gateway->delete($this->prefix . $key);
    }

    public function clear(): bool
    {
        return $this->gateway->clear();
    }

    public function has(string $key): bool
    {
        return $this->gateway->has($this->prefix . $key);
    }
}
