<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures;

use Fundrik\WordPress\Infrastructure\Ports\Storage\StorageExceptionInterface;
use RuntimeException;

final class FakeStorageException extends RuntimeException implements StorageExceptionInterface {}
