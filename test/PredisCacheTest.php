<?php declare(strict_types=1);

namespace Vulpes\SimpleCache;

use DateInterval;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Predis\Client;

//use Predis\Response\Status;
use Predis\ClientInterface;
use Psr\SimpleCache\CacheInterface;
use Throwable;

class PredisCacheTest extends TestCase
{
    private MockObject|ClientInterface $client;

    protected function setUp(): void
    {
        $this->client = $this->getMockBuilder(Client::class)
            ->addMethods(['get', 'set', 'del', 'keys', 'exists'])->getMock();
    }

    public function testInstanceOfPsr(): void
    {
        $cache = new PredisCache($this->client);
        self::assertInstanceOf(CacheInterface::class, $cache);
    }

    /**
     * @dataProvider getProvider
     */
    public function testGet(string $prefix, string $key, bool $exists, mixed $value, mixed $default, mixed $expected): void
    {
        $this->client->expects($this->any())->method('exists')->willReturn($exists);
        $this->client->expects($this->any())->method('get')
            ->with($prefix . $key)->willReturn($value ? serialize($value) : null);

        $cache = new PredisCache($this->client, $prefix);
        $result = $cache->get($key, $default);

        self::assertEquals($expected, $result);
        self::assertEquals($prefix, $cache->getPrefix());
    }

    public function getProvider(): array
    {
        $interval = new DateInterval('PT35S');
        return [
            ['prefix:', 'key-01', true, 'value-01', 'default-value', 'value-01'],
            ['prefix:', 'key-01', true, $interval, 'default-value', $interval],
            ['prefix:', 'key-01', false, null, 'default-value', 'default-value'],
            ['prefix:', 'key-01', true, null, 'default-value', null],
            ['prefix:', 'key-01', true, null, $interval, null],
            ['prefix:', 'key-01', false, null, $interval, $interval],
            ['prefix:', 'key-01', true, null, null, null],
        ];
    }

    /**
     * @dataProvider setProvider
     */
    public function testSet(string $prefix, string $key, mixed $value, DateInterval|int|null $ttl, array $withTtl, bool $return, bool $expected): void
    {
        $this->client->expects($this->once())->method('set')
            ->with($prefix . $key, serialize($value), ...$withTtl)->willReturn($return);

        $cache = new PredisCache($this->client, $prefix);
        $result = $cache->set($key, $value, $ttl);

        self::assertEquals($expected, $result);
    }

    public function setProvider(): array
    {
//        $status = new Status('OK');
        $interval = new DateInterval('PT35S');
        return [
            ['prefix:', 'key-01', 'value-01', $interval, ['EX', 35], true, true],
            ['prefix:', 'key-01', 'value-01', 10, ['EX', 10], true, true],
            ['prefix:', 'key-01', 'value-01', null, [], true, true],
        ];
    }


    /**
     * @dataProvider deleteProvider
     */
    public function testDelete(string $prefix, string $key, bool $return, bool $expected): void
    {
        $this->client->expects($this->once())->method('del')
            ->with($prefix . $key)->willReturn($return);

        $cache = new PredisCache($this->client, $prefix);
        $result = $cache->delete($key);

        self::assertEquals($expected, $result);
    }

    public function deleteProvider(): array
    {
//        $status = new Status('OK');
        return [
            ['prefix:', 'key-01', true, true],
        ];
    }


    /**
     * @dataProvider hasProvider
     */
    public function testHas(string $defaultPrefix, string $key, bool $return, bool $expected): void
    {
        $this->client->expects($this->once())->method('exists')
            ->with($defaultPrefix . $key)->willReturn($return);

        $cache = new PredisCache($this->client, $defaultPrefix);
        $result = $cache->has($key);

        self::assertEquals($expected, $result);
    }

    public function hasProvider(): array
    {
        return [
            ['prefix:', 'key-01', true, true]
        ];
    }

    /**
     * @dataProvider clearProvider
     */
    public function testClear(string $defaultPrefix, array $keys, ?Throwable $e, $return, $expected): void
    {
        $j = 0;
        $this->client->expects($this->once())->method('keys')
            ->with($defaultPrefix . "*")->willReturnCallback(function () use ($keys, $e) {
                if ($e) {
                    throw $e;
                }
                return $keys;
            });

        $this->client->expects($this->exactly($e ? 0 : count($keys)))->method('del')
            ->willReturnCallback(function (string $key) use ($defaultPrefix, $keys, &$j, $return): mixed {

                self::assertSame($defaultPrefix . $keys[$j], $key);

                return $return[$j++];
            });


        $cache = new PredisCache($this->client, $defaultPrefix);
        $result = $cache->clear();

        self::assertEquals($expected, $result);
    }

    public function clearProvider(): array
    {
        $e = new InvalidArgumentException;
        return [
            ['prefix:', ['key-01', 'key-02'], null, [true, true], true],
            ['prefix:', ['key-01', 'key-02'], null, [false, true], false],
            ['prefix:', ['key-01', 'key-02'], null, [true, false], false],
            ['prefix:', ['key-01', 'key-02'], null, [false, false], false],
            ['prefix:', ['key-01', 'key-02'], $e, [true, true], false],
        ];
    }
}
