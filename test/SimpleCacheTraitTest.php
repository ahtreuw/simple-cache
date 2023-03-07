<?php declare(strict_types=1);

namespace Vulpes\SimpleCache;

use DateInterval;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;

class SimpleCacheTraitTest extends TestCase
{
    private MockObject|CacheInterface $gateway;

    protected function setUp(): void
    {
        $this->prefix = 'prefix:';
        $this->gateway = $this->createMock(CacheInterface::class);
    }

    /**
     * @dataProvider getMultipleProvider
     */
    public function testGetMultiple(array $keys, string|array $defaultValues, array $expected): void
    {
        $j = 0;
        $this->gateway->expects($this->any())->method('has')->willReturn(true);
        $this->gateway->expects($this->exactly(count($keys)))->method('get')
            ->willReturnCallback(function (string $key, mixed $defaultValue) use ($keys, $defaultValues, &$j): mixed {
                self::assertSame($keys[$j++], $key);

                if (is_string($defaultValues)) {
                    $returnValue = $defaultValues;
                    self::assertSame($defaultValues, $defaultValue);
                } else {
                    $returnValue = $defaultValues[$key];
                    self::assertSame($defaultValues[$key], $defaultValue);
                }

                return $returnValue;
            });

        $cache = new CacheDecorator($this->gateway);

        $result = $cache->getMultiple($keys, $defaultValues);

        self::assertEquals($expected, $result);
    }

    public function getMultipleProvider(): array
    {
        return [
            [['key-01', 'key-02'], 'default-value', ['key-01' => 'default-value', 'key-02' => 'default-value']],
            [['key-01', 'key-02'], ['key-01' => 'def-01', 'key-02' => 'def-02'], ['key-01' => 'def-01', 'key-02' => 'def-02']],
        ];
    }

    /**
     * @dataProvider setMultipleProvider
     */
    public function testSetMultiple(array $data, DateInterval|int|null $defaultTtl, DateInterval|int|null $ttl, DateInterval|int|null $withTtl, array $return): void
    {
        $returnIndex = 0;
        $this->gateway->expects($this->exactly(count($data)))->method('set')
            ->willReturnCallback(function (string $key, mixed $value, DateInterval|int|null $ttl) use ($data, $withTtl, $return, &$returnIndex): bool {

                $index = array_search($value, $data);
                self::assertSame($data[$index], $value);
                self::assertSame($withTtl, $ttl);

                return $return[$returnIndex++];
            });

        $cache = new CacheDecorator($this->gateway, $this->prefix, $defaultTtl);

        $result = $cache->setMultiple($data, $ttl);

        self::assertEquals(in_array(false, $return) === false, $result);
    }

    public function setMultipleProvider(): array
    {
        $defaultTtl = new DateInterval('PT35S');
        $ttl = new DateInterval('PT35S');
        return [
            [['key-01' => 'v-01', 'key-02' => 'v-02'], $defaultTtl, $ttl, $ttl, [true, true]],
            [['key-01' => 'v-01', 'key-02' => 'v-02'], 10, null, 10, [true, true]],
            [['key-01' => 'v-01', 'key-02' => 'v-02'], null, 10, 10, [false, true]],
            [['key-01' => 'v-01', 'key-02' => 'v-02'], null, null, null, [true, false]],
        ];
    }

    /**
     * @dataProvider deleteMultipleProvider
     */
    public function testDeleteMultiple(array $keys, array $return, bool $expected): void
    {
        $j = 0;
        $this->gateway->expects($this->exactly(count($keys)))->method('delete')
            ->willReturnCallback(function (string $key) use ($keys, &$j, $return): mixed {
                self::assertSame($keys[$j], $key);
                return $return[$j++];
            });

        $cache = new CacheDecorator($this->gateway);

        $result = $cache->deleteMultiple($keys);

        self::assertEquals($expected, $result);
    }

    public function deleteMultipleProvider(): array
    {
        return [
            [['key-01', 'key-02'], [true, true], true],
            [['key-01', 'key-02'], [false, true], false],
            [['key-01', 'key-02'], [true, false], false],
            [['key-01', 'key-02'], [false, false], false],
        ];
    }

}
