<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Components\Campaigns\Application\Exceptions;

use Fundrik\Core\Components\Campaigns\Application\Exceptions\CampaignDtoFactoryExceptionInterface;

// phpcs:disable SlevomatCodingStandard.Files.LineLength.LineTooLong
/**
 * Thrown when the WordPress CampaignDtoFactory fails to create a DTO from input data.
 *
 * @since 1.0.0
 */
final class CampaignDtoFactoryException extends CampaignApplicationException implements CampaignDtoFactoryExceptionInterface {}
// phpcs:enable
