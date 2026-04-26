<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Ids;

use InvalidArgumentException;

/**
 * Thrown when a donation ID cannot be resolved.
 *
 * @since 1.0.0
 *
 * @internal
 */
final class DonationIdException extends InvalidArgumentException {}
