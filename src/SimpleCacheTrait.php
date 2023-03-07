<?php declare(strict_types=1);

namespace Vulpes\SimpleCache;

use DateInterval;

trait SimpleCacheTrait
{

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $values = [];

        foreach ($keys as $key) {
            $defaultValue = is_array($default) && array_key_exists($key, $default) ? $default[$key] : $default;
            $values[$key] = $this->get($key, $defaultValue);
        }

        return $values;
    }

    public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool
    {
        $result = true;

        foreach ($values as $key => $value) {
            $result = $this->set($key, $value, $ttl) && $result;
        }

        return $result;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        $result = true;

        foreach ($keys as $key) {
            $result = $this->delete($key) && $result;
        }

        return $result;
    }
}
