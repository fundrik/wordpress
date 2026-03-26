<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Repositories\CampaignRepository;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignNotFoundExceptionInterface;
use RuntimeException;
use Throwable;

/**
 * Thrown when persisting a campaign fails because the campaign ID does not exist.
 *
 * @since 1.0.0
 *
 * @internal
 */
final class CampaignNotFoundException extends RuntimeException implements CampaignNotFoundExceptionInterface {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param int $campaign_id The missing campaign ID.
	 * @param string $action The repository action being performed.
	 * @param Throwable|null $previous The previous low-level exception.
	 */
	public function __construct( int $campaign_id, string $action, ?Throwable $previous = null ) {

		parent::__construct(
			sprintf(
				'Cannot %s campaign "%d": persisted record not found.',
				$action,
				$campaign_id,
			),
			0,
			$previous,
		);
	}
}
