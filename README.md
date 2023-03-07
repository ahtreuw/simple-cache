# Simple cache
This repository contains the [PHP FIG PSR-16] Simple cache implementation.

## Install
Via Composer
Package is available on [Packagist], you can install it using [Composer].
``` bash
$ composer require vulpes/simple-cache
```

## Use PredisCache
To use PredisCache, you need to install the predis package:
``` bash
$ composer require predis/predis
```
```php
$pdo = new PDO(getenv('MYSQL_DSN'));
$client = new Predis\Client(getenv('REDIS_DSN'));

$cache = new Vulpes\SimpleCache\PredisCache($client);

$logger = new class (STDOUT) {
    public function __construct(private $output) {}
    public function log(string $message): void
    {
        fwrite($this->output, $message . PHP_EOL);
    }
};

$model = new class ($pdo, $cache, $logger) {

    public function __construct(
        private PDO                            $pdo,
        private Psr\SimpleCache\CacheInterface $cache,
        private                                $logger
    ) {}

    public function getCategories(): array
    {
        if ($this->cache->has('categories')) {

            $this->logger->log('GET categories from CACHE');

            return $this->cache->get('categories');
        }

        $this->logger->log('GET categories from DATABASE');

        $statement = $this->pdo->prepare('SELECT id, `name` FROM categories');
        $statement->execute();
        $categories = $statement->fetchAll(PDO::FETCH_KEY_PAIR);

        $this->cache->set('categories', $categories, 1);

        return $categories;
    }
};

$model->getCategories();
// STDOUT: GET categories from DATABASE

$model->getCategories();
// STDOUT: GET categories from CACHE

sleep(1);

$model->getCategories();
// STDOUT: GET categories from DATABASE
```
[PHP FIG PSR-20]: https://www.php-fig.org/psr/psr-16/
[Packagist]: http://packagist.org/packages/vulpes/simple-cache
[Composer]: http://getcomposer.org
