<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure;

/**
 * Marks database write failures caused by duplicate-key violations.
 *
 * @since 1.0.0
 *
 * @internal
 */
interface DatabaseDuplicateKeyExceptionInterface extends DatabaseExceptionInterface {}
