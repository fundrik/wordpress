<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures;

use Fundrik\WordPress\Infrastructure\Ports\Database\DatabaseExceptionInterface;
use RuntimeException;

/**
 * Represents a database failure in tests.
 */
final class FakeDatabaseException extends RuntimeException implements DatabaseExceptionInterface {}
