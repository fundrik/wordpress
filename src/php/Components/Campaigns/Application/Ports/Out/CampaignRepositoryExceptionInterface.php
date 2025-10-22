<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Components\Campaigns\Application\Ports\Out;

use Fundrik\Core\Components\Campaigns\Application\Ports\Out\CampaignRepositoryExceptionInterface as CoreCampaignRepositoryExceptionInterface;

/**
 * Marks all exceptions that occur in campaign repository operations.
 *
 * @since 0.1.0
 */
interface CampaignRepositoryExceptionInterface extends CoreCampaignRepositoryExceptionInterface {}
