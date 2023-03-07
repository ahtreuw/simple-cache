<?php declare(strict_types=1);

namespace Vulpes\SimpleCache;

use Exception;

class InvalidArgumentException extends Exception implements \Psr\SimpleCache\InvalidArgumentException {}
