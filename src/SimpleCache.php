<?php declare(strict_types=1);

namespace Vulpes\SimpleCache;

use DateInterval;
use Psr\SimpleCache\CacheInterface;

class SimpleCache implements CacheInterface
{
    use SimpleCacheTrait;

    public function __construct(
        private CacheInterface|Gateway $gateway,
        private DateInterval|int|null  $defaultTtl = null
    ) {}

    public function get(string $key, mixed $default = null): mixed
    {
        if($this->has($key)){
            return $this->gateway->get($this->getPrefix() . $key, $default);
        }
        return $default;
    }

    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        return $this->gateway->set($this->getPrefix() . $key, $value, $ttl ?: $this->defaultTtl);
    }

    public function delete(string $key): bool
    {
        return $this->gateway->delete($this->getPrefix() . $key);
    }

    public function clear(): bool
    {
        return $this->gateway->clear();
    }

    public function has(string $key): bool
    {
        return $this->gateway->has($this->getPrefix() . $key);
    }

    private function getPrefix(): string
    {
        if ($this->gateway instanceof Gateway) {
            return $this->gateway->getPrefix();
        }
        return '';
    }
}
