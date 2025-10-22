<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Components\Campaigns\Application\Exceptions;

use Fundrik\Core\Components\Campaigns\Application\Exceptions\CampaignAssemblerExceptionInterface;

// phpcs:disable SlevomatCodingStandard.Files.LineLength.LineTooLong
/**
 * Thrown when the WordPress CampaignAssembler fails.
 *
 * @since 1.0.0
 */
final class CampaignAssemblerException extends CampaignApplicationException implements CampaignAssemblerExceptionInterface {}
// phpcs:enable