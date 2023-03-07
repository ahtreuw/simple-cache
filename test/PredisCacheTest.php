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
        $this->prefix = 'prefix:';
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
    public function testGet(string $key, bool $exists, mixed $value, mixed $default, mixed $expected): void
    {
        $this->client->expects($this->any())->method('exists')->willReturn($exists);
        $this->client->expects($this->any())->method('get')
            ->with($this->prefix . $key)->willReturn($value ? serialize($value) : null);

        $cache = new PredisCache($this->client, $this->prefix);
        $result = $cache->get($key, $default);

        self::assertEquals($expected, $result);
    }

    public function getProvider(): array
    {
        $interval = new DateInterval('PT35S');
        return [
            ['key-01', true, 'value-01', 'default-value', 'value-01'],
            ['key-01', true, $interval, 'default-value', $interval],
            ['key-01', false, null, 'default-value', 'default-value'],
            ['key-01', true, null, 'default-value', null],
            ['key-01', true, null, $interval, null],
            ['key-01', false, null, $interval, $interval],
            ['key-01', true, null, null, null],
        ];
    }

    /**
     * @dataProvider setProvider
     */
    public function testSet(string $key, mixed $value, DateInterval|int|null $ttl, array $withTtl, bool $return, bool $expected): void
    {
        $this->client->expects($this->once())->method('set')
            ->with($this->prefix . $key, serialize($value), ...$withTtl)->willReturn($return);

        $cache = new PredisCache($this->client, $this->prefix);
        $result = $cache->set($key, $value, $ttl);

        self::assertEquals($expected, $result);
    }

    public function setProvider(): array
    {
//        $status = new Status('OK');
        $interval = new DateInterval('PT35S');
        return [
            ['key-01', 'value-01', $interval, ['EX', 35], true, true],
            ['key-01', 'value-01', 10, ['EX', 10], true, true],
            ['key-01', 'value-01', null, [], true, true],
        ];
    }


    /**
     * @dataProvider deleteProvider
     */
    public function testDelete(string $key, bool $return, bool $expected): void
    {
        $this->client->expects($this->once())->method('del')
            ->with($this->prefix . $key)->willReturn($return);

        $cache = new PredisCache($this->client, $this->prefix);
        $result = $cache->delete($key);

        self::assertEquals($expected, $result);
    }

    public function deleteProvider(): array
    {
//        $status = new Status('OK');
        return [
            ['key-01', true, true],
        ];
    }


    /**
     * @dataProvider hasProvider
     */
    public function testHas(string $key, bool $return, bool $expected): void
    {
        $this->client->expects($this->once())->method('exists')
            ->with($this->prefix . $key)->willReturn($return);

        $cache = new PredisCache($this->client, $this->prefix);
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
    public function testClear(array $keys, ?Throwable $e, $return, $expected): void
    {
        $j = 0;
        $this->client->expects($this->once())->method('keys')
            ->with($this->prefix . "*")->willReturnCallback(function () use ($keys, $e) {
                if ($e) {
                    throw $e;
                }
                return $keys;
            });

        $this->client->expects($this->exactly($e ? 0 : count($keys)))->method('del')
            ->willReturnCallback(function (string $key) use ($keys, &$j, $return): mixed {

                self::assertSame($this->prefix . $keys[$j], $key);

                return $return[$j++];
            });


        $cache = new PredisCache($this->client, $this->prefix);
        $result = $cache->clear();

        self::assertEquals($expected, $result);
    }

    public function clearProvider(): array
    {
        $e = new InvalidArgumentException;
        return [
            [['key-01', 'key-02'], null, [true, true], true],
            [['key-01', 'key-02'], null, [false, true], false],
            [['key-01', 'key-02'], null, [true, false], false],
            [['key-01', 'key-02'], null, [false, false], false],
            [['key-01', 'key-02'], $e, [true, true], false],
        ];
    }
}
