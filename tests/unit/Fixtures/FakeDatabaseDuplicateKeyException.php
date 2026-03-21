<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures;

use Fundrik\WordPress\Infrastructure\DatabaseDuplicateKeyExceptionInterface;
use RuntimeException;

/**
 * Test-only database exception for duplicate-key violations.
 */
final class FakeDatabaseDuplicateKeyException extends RuntimeException implements DatabaseDuplicateKeyExceptionInterface {}
