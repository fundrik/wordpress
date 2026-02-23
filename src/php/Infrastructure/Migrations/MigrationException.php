<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Migrations;

use Fundrik\WordPress\Kernel\Ports\MigrationRunnerExceptionInterface;
use RuntimeException;

/**
 * Thrown when a database migration cannot be applied or completed.
 *
 * @since 1.0.0
 *
 * @internal
 */
final class MigrationException extends RuntimeException implements MigrationRunnerExceptionInterface {}
