<?php declare(strict_types=1);

namespace Vulpes\SimpleCache;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;

class SimpleCacheTest extends TestCase
{
    private MockObject|CacheInterface|Gateway $gateway;

    protected function setUp(): void
    {
        $this->gateway = $this->createMock(Gateway::class);
    }

    public function testInstanceOfPsr(): void
    {
        $cache = new SimpleCache($this->gateway);
        self::assertInstanceOf(CacheInterface::class, $cache);
    }

    /**
     * @dataProvider getProvider
     */
    public function testGet(string $prefix, string $key, bool $exists, mixed $cachedValue, mixed $defaultValue, mixed $expected): void
    {
        $this->gateway->expects($this->once())->method('has')->with($prefix . $key)->willReturn($exists);
        $this->gateway->expects($this->any())->method('get')->with($prefix . $key)->willReturn($cachedValue);
        $this->gateway->expects($this->any())->method('getPrefix')->willReturn($prefix);

        $cache = new SimpleCache($this->gateway);
        $result = $cache->get($key, $defaultValue);

        self::assertEquals($expected, $result);
    }

    public function getProvider(): array
    {
        return [
            ['prefix0:', 'key-01', true, 'cachedValue', 'defaultValue', 'cachedValue'],
            ['prefix1:', 'key-02', false, null, 'defaultValue', 'defaultValue'],
            ['prefix1:', 'key-02', true, null, 'defaultValue', null],
            ['prefix2:', 'key-03', false, 'cachedValue', null, null],
        ];
    }


    /**
     * @dataProvider hasProvider
     */
    public function testHas(string $key, bool $return, bool $expected): void
    {
        $this->gateway->expects($this->once())->method('has')->with($key)->willReturn($return);

        $cache = new SimpleCache($this->gateway);
        $result = $cache->has($key);

        self::assertEquals($expected, $result);
    }

    public function hasProvider(): array
    {
        return [
            ['key-01', true, true]
        ];
    }


    /**
     * @dataProvider clearProvider
     */
    public function testClear(bool $return, bool $expected): void
    {
        $this->gateway->expects($this->once())->method('clear')->with()->willReturn($return);

        $cache = new SimpleCache($this->gateway);
        $result = $cache->clear();

        self::assertEquals($expected, $result);
    }

    public function clearProvider(): array
    {
        return [
            [true, true],
            [false, false],
        ];
    }
}
