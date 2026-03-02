<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure;

use Throwable;

/**
 * Marks all exceptions that occur in database operations.
 *
 * @since 1.0.0
 *
 * @internal
 */
interface DatabaseExceptionInterface extends Throwable {}
