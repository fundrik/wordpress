<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Components\Campaigns\Domain\Exceptions;

use InvalidArgumentException;

/**
 * Thrown when a campaign ID cannot be resolved.
 *
 * @since 1.0.0
 *
 * @internal
 */
final class CampaignIdException extends InvalidArgumentException {}
