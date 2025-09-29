<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Components\Campaigns\Domain;

use Fundrik\Core\Components\Campaigns\Domain\Campaign as CoreCampaign;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Support\TypeCaster;
use Fundrik\WordPress\Components\Campaigns\Domain\Exceptions\InvalidCampaignException;
use InvalidArgumentException;

/**
 * Represents a fundraising campaign with WordPress-specific data.
 *
 * @since 1.0.0
 */
final readonly class Campaign {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param CoreCampaign $core_campaign The core campaign entity.
	 * @param CampaignSlug $slug The campaign slug.
	 */
	public function __construct(
		private CoreCampaign $core_campaign,
		private CampaignSlug $slug,
	) {}

	/**
	 * Returns the campaign ID.
	 *
	 * @since 1.0.0
	 *
	 * @return int The campaign ID.
	 */
	public function get_id(): int {

		$value = $this->core_campaign->get_id();

		try {
			return TypeCaster::to_int( $value );
		} catch ( InvalidArgumentException $e ) {

			throw new InvalidCampaignException(
				sprintf(
					'Expected int-compatible ID in Campaign, got invalid value: %s',
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export
					var_export( $value, true ),
				),
				previous: $e,
			);
		}
	}

	/**
	 * Returns the campaign ID value object.
	 *
	 * @since 1.0.0
	 *
	 * @return EntityId The campaign ID value object.
	 */
	public function get_entity_id(): EntityId {

		return $this->core_campaign->get_entity_id();
	}

	/**
	 * Returns the campaign title.
	 *
	 * @since 1.0.0
	 *
	 * @return string The campaign title.
	 */
	public function get_title(): string {

		return $this->core_campaign->get_title();
	}

	/**
	 * Returns whether the campaign is active.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if the campaign is active.
	 */
	public function is_active(): bool {

		return $this->core_campaign->is_active();
	}

	/**
	 * Returns whether the campaign is open for donations.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if the campaign is currently open.
	 */
	public function is_open(): bool {

		return $this->core_campaign->is_open();
	}

	/**
	 * Returns whether the campaign has a target.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if the campaign has a target.
	 */
	public function has_target(): bool {

		return $this->core_campaign->has_target();
	}

	/**
	 * Returns the campaign target amount.
	 *
	 * Returns zero if targeting is disabled.
	 *
	 * @since 1.0.0
	 *
	 * @return int The target amount in minor units.
	 */
	public function get_target_amount(): int {

		return $this->core_campaign->get_target_amount();
	}

	/**
	 * Returns the campaign slug.
	 *
	 * @since 1.0.0
	 *
	 * @return string The campaign slug.
	 */
	public function get_slug(): string {

		return $this->slug->value;
	}
}
