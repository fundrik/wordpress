<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Components\Campaigns\Domain\Exceptions;

use Fundrik\WordPress\Components\Shared\Domain\Exceptions\FundrikWordPressDomainException;

/**
 * Serves as the base exception for WordPress campaign domain errors.
 *
 * @since 1.0.0
 */
abstract class CampaignDomainException extends FundrikWordPressDomainException {}
