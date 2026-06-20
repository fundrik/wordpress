<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Components\Campaigns\Domain;

/**
 * Resolves campaign fundraising status from campaign facts.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class CampaignStatusPolicy {

	/**
	 * Returns the campaign fundraising status.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $accepts_donations Whether the campaign accepts donations.
	 * @param int|null $target_amount Campaign target amount, if configured.
	 * @param int $collected_amount Campaign collected amount.
	 *
	 * @return string Campaign fundraising status.
	 */
	public function resolve( bool $accepts_donations, ?int $target_amount, int $collected_amount ): string {

		if ( ! $accepts_donations ) {
			return 'donations_disabled';
		}

		if ( $target_amount !== null && $collected_amount >= $target_amount ) {
			return 'target_reached';
		}

		return 'active';
	}
}
