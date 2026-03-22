<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Database;

use Fundrik\WordPress\Infrastructure\Ports\Database\DatabaseDuplicateKeyExceptionInterface;
use RuntimeException;

/**
 * Thrown when a wpdb write operation fails because of a duplicate key.
 *
 * @since 1.0.0
 *
 * @internal
 */
final class WpdbDuplicateKeyException extends RuntimeException implements DatabaseDuplicateKeyExceptionInterface {}
