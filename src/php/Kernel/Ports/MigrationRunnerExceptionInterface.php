<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Kernel\Ports;

use Throwable;

/**
 * Marks all exceptions that occur during migration running.
 *
 * @since 1.0.0
 *
 * @internal
 */
interface MigrationRunnerExceptionInterface extends Throwable {}
