<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Components\Campaigns\Domain\Exceptions;

use Fundrik\Core\Components\Shared\Domain\Exceptions\FundrikDomainException;

/**
 * Serves as the base exception for campaign domain errors.
 *
 * @since 1.0.0
 *
 * @internal
 */
abstract class CampaignDomainException extends FundrikDomainException {}
