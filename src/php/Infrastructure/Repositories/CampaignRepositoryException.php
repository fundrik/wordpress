<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Repositories;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryExceptionInterface;
use RuntimeException;

/**
 * Thrown when campaign persistence operation fails.
 *
 * @since 1.0.0
 */
final class CampaignRepositoryException extends RuntimeException implements CampaignRepositoryExceptionInterface {}
