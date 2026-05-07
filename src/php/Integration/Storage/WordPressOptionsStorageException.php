<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Storage;

use Fundrik\WordPress\Infrastructure\Ports\Storage\StorageExceptionInterface;
use RuntimeException;

/**
 * Thrown when a WordPress options storage operation fails.
 *
 * @since 1.0.0
 *
 * @internal
 */
final class WordPressOptionsStorageException extends RuntimeException implements StorageExceptionInterface {}
