<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Database;

use Fundrik\WordPress\Infrastructure\Ports\Database\DatabaseRowNotFoundExceptionInterface;
use RuntimeException;

/**
 * Thrown when a wpdb write operation targets a row that does not exist.
 *
 * @since 1.0.0
 *
 * @internal
 */
final class WpdbRowNotFoundException extends RuntimeException implements DatabaseRowNotFoundExceptionInterface {}
