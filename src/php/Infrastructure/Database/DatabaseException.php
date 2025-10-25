<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Database;

use RuntimeException;

/**
 * Thrown when a database operation fails.
 *
 * @since 1.0.0
 *
 * @internal
 */
final class DatabaseException extends RuntimeException {}
