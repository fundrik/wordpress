<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Database;

use Fundrik\WordPress\Infrastructure\Ports\Database\DatabaseExceptionInterface;
use RuntimeException;

/**
 * Thrown when a wpdb database operation fails.
 *
 * @since 1.0.0
 *
 * @internal
 */
final class WpdbDatabaseException extends RuntimeException implements DatabaseExceptionInterface {}
