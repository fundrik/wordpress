<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Components\Campaigns\Domain\Exceptions;

/**
 * Thrown when a campaign ID is not integer-compatible in the WordPress context.
 *
 * @since 1.0.0
 */
final class InvalidCampaignIdException extends CampaignDomainException {}
