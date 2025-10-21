<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Components\Campaigns\Domain\Exceptions;

/**
 * Thrown when the campaign target amount is inconsistent with the target state.
 *
 * @since 1.0.0
 */
final class InvalidCampaignTargetException extends CampaignDomainException {}
