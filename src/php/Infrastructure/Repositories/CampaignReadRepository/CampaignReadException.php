<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Repositories\CampaignReadRepository;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRead\CampaignReadExceptionInterface;
use RuntimeException;

/**
 * Thrown when a campaign read operation fails.
 *
 * @since 1.0.0
 *
 * @internal
 */
final class CampaignReadException extends RuntimeException implements CampaignReadExceptionInterface {}
