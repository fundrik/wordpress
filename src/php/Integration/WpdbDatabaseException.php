<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration;

use Fundrik\WordPress\Infrastructure\DatabaseExceptionInterface;
use RuntimeException;

/**
 * Thrown when a wpdb database operation fails.
 *
 * @since 1.0.0
 *
 * @internal
 */
final class WpdbDatabaseException extends RuntimeException implements DatabaseExceptionInterface {}
