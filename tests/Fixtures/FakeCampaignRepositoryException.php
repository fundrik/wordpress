<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures;

use Exception;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryExceptionInterface;

final class FakeCampaignRepositoryException extends Exception implements CampaignRepositoryExceptionInterface {}
