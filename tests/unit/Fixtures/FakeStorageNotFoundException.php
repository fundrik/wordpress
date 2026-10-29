<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures;

use Fundrik\WordPress\Infrastructure\Ports\Storage\StorageNotFoundExceptionInterface;
use RuntimeException;

/**
 * Represents a missing storage value in tests.
 */
final class FakeStorageNotFoundException extends RuntimeException implements StorageNotFoundExceptionInterface {}
