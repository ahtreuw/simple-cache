<?php declare(strict_types=1);

namespace Vulpes\SimpleCache;

use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;

class NullCacheTest extends TestCase
{
    public function testNullCache(): void
    {
        $simpleCache = new NullCache;

        self::assertInstanceOf(CacheInterface::class, $simpleCache);

        self::assertNull($simpleCache->get('key'));
        self::assertFalse($simpleCache->has('key'));
        self::assertFalse($simpleCache->delete('key'));
        self::assertFalse($simpleCache->set('key', 'value'));
        self::assertFalse($simpleCache->clear());
    }
}
