<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Components\Campaigns\Domain\Exceptions;

/**
 * Thrown when the campaign slug is empty or consists solely of whitespace.
 *
 * @since 1.0.0
 */
final class InvalidCampaignSlugException extends CampaignDomainException {}
