<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures;

use Fundrik\WordPress\Infrastructure\Ports\Database\DatabaseDuplicateKeyExceptionInterface;
use RuntimeException;

/**
 * Represents a duplicate database key failure in tests.
 */
final class FakeDatabaseDuplicateKeyException extends RuntimeException implements DatabaseDuplicateKeyExceptionInterface {}
