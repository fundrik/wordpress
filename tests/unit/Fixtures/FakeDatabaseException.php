<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures;

use Fundrik\WordPress\Infrastructure\Ports\Database\DatabaseExceptionInterface;
use RuntimeException;

/**
 * Test-only database exception implementing the DB port exception contract.
 */
final class FakeDatabaseException extends RuntimeException implements DatabaseExceptionInterface {}
