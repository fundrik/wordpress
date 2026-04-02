<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Ports\Storage;

use Throwable;

/**
 * Marks all exceptions that occur in storage operations.
 *
 * @since 1.0.0
 *
 * @internal
 */
interface StorageExceptionInterface extends Throwable {}
