<?php declare(strict_types=1);

namespace Vulpes\SimpleCache;

use DateInterval;
use Predis\ClientInterface;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

class PredisCache implements CacheInterface
{
    use SimpleCacheTrait;

    public function __construct(
        protected ClientInterface $client,
        protected string          $prefix = ''
    ) {}

    public function get(string $key, mixed $default = null): mixed
    {
        if ($this->has($key) === false) {
            return $default;
        }

        $value = $this->client->get($this->prefix . $key);

        return is_null($value) ? $value : unserialize($value);
    }

    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        if ($ttl = $this->prepareTTL($ttl)) {
            $this->client->set($this->prefix . $key, serialize($value), 'EX', $ttl);
        } else {
            $this->client->set($this->prefix . $key, serialize($value));
        }
        return true;
    }

    public function delete(string $key): bool
    {
        return (bool)$this->client->del($this->prefix . $key);
    }

    public function clear(): bool
    {
        $result = false;
        try {
            $keys = $this->client->keys($this->prefix . "*");
            if (count($keys)) {
                $result = $this->deleteMultiple($keys);
            }
        } catch (InvalidArgumentException $e) {
            return false;
        }
        return $result;
    }

    public function has(string $key): bool
    {
        return (bool)$this->client->exists($this->prefix . $key);
    }

    protected function prepareTTL(DateInterval|int|null $ttl): ?int
    {
        if ($ttl instanceof DateInterval) {
            return date_create('@0')->add($ttl)->getTimestamp();
        }
        return is_int($ttl) ? $ttl : null;
    }
}
