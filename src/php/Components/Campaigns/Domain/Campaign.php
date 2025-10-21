<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Components\Campaigns\Domain;

use Fundrik\Core\Components\Campaigns\Domain\Campaign as CoreCampaign;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTitle;
use Fundrik\Core\Components\Campaigns\Domain\Exceptions\CampaignChangeException as CoreCampaignChangeException;
use Fundrik\Core\Components\Campaigns\Domain\Exceptions\InvalidCampaignTargetException as CoreInvalidCampaignTargetException;
use Fundrik\Core\Components\Campaigns\Domain\Exceptions\InvalidCampaignTitleException as CoreInvalidCampaignTitleException;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidEntityIdException;
use Fundrik\WordPress\Components\Campaigns\Domain\Exceptions\CampaignChangeException;
use Fundrik\WordPress\Components\Campaigns\Domain\Exceptions\InvalidCampaignIdException;
// phpcs:disable SlevomatCodingStandard.Namespaces.UnusedUses.UnusedUse
use Fundrik\WordPress\Components\Campaigns\Domain\Exceptions\InvalidCampaignSlugException;
// phpcs:enable
use Fundrik\WordPress\Components\Campaigns\Domain\Exceptions\InvalidCampaignTargetException;
use Fundrik\WordPress\Components\Campaigns\Domain\Exceptions\InvalidCampaignTitleException;

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
	 *
	 * @throws InvalidCampaignIdException When the underlying ID cannot be represented as int.
	 */
	public function get_id(): int {

		try {
			return $this->core_campaign->get_entity_id()->get_as_int();
		} catch ( InvalidEntityIdException $e ) {

			throw new InvalidCampaignIdException(
				sprintf(
					'Campaign ID must be an integer compatible with WordPress storage. Given: %s.',
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export
					var_export( $this->core_campaign->get_id(), true ),
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

		return $this->slug->get_value();
	}

	/**
	 * Changes the campaign title.
	 *
	 * @since 1.0.0
	 *
	 * @param string|CampaignTitle $new_title The new title.
	 *
	 * @return self The campaign with updated title.
	 *
	 * @throws InvalidCampaignTitleException When the provided title is invalid.
	 * @throws CampaignChangeException When the title matches the current one.
	 */
	public function rename( string|CampaignTitle $new_title ): self {

		try {
			return new self(
				$this->core_campaign->rename( $new_title ),
				$this->slug,
			);
		} catch ( CoreInvalidCampaignTitleException $e ) {

			throw new InvalidCampaignTitleException( $e->getMessage(), previous: $e );
		} catch ( CoreCampaignChangeException $e ) {

			throw new CampaignChangeException( $e->getMessage(), previous: $e );
		}
	}

	/**
	 * Activates the campaign.
	 *
	 * @since 1.0.0
	 *
	 * @return self The campaign in active state.
	 *
	 * @throws CampaignChangeException When the campaign is already active.
	 */
	public function activate(): self {

		try {
			return new self(
				$this->core_campaign->activate(),
				$this->slug,
			);
		} catch ( CoreCampaignChangeException $e ) {

			throw new CampaignChangeException( $e->getMessage(), previous: $e );
		}
	}

	/**
	 * Deactivates the campaign.
	 *
	 * @since 1.0.0
	 *
	 * @return self The campaign in inactive state.
	 *
	 * @throws CampaignChangeException When the campaign is already inactive.
	 */
	public function deactivate(): self {

		try {
			return new self(
				$this->core_campaign->deactivate(),
				$this->slug,
			);
		} catch ( CoreCampaignChangeException $e ) {

			throw new CampaignChangeException( $e->getMessage(), previous: $e );
		}
	}

	/**
	 * Opens the campaign for donations.
	 *
	 * @since 1.0.0
	 *
	 * @return self The campaign in open state.
	 *
	 * @throws CampaignChangeException When the campaign is already open.
	 */
	public function open(): self {

		try {
			return new self(
				$this->core_campaign->open(),
				$this->slug,
			);
		} catch ( CoreCampaignChangeException $e ) {

			throw new CampaignChangeException( $e->getMessage(), previous: $e );
		}
	}

	/**
	 * Closes the campaign for donations.
	 *
	 * @since 1.0.0
	 *
	 * @return self The campaign in closed state.
	 *
	 * @throws CampaignChangeException When the campaign is already closed.
	 */
	public function close(): self {

		try {
			return new self(
				$this->core_campaign->close(),
				$this->slug,
			);
		} catch ( CoreCampaignChangeException $e ) {

			throw new CampaignChangeException( $e->getMessage(), previous: $e );
		}
	}

	/**
	 * Enables targeting with the specified amount.
	 *
	 * @since 1.0.0
	 *
	 * @param int $amount The positive target amount in minor currency units.
	 *
	 * @return self The campaign with targeting enabled and amount set.
	 *
	 * @throws InvalidCampaignTargetException When the amount is invalid.
	 * @throws CampaignChangeException When targeting is already enabled with the same amount.
	 */
	public function enable_target( int $amount ): self {

		try {
			return new self(
				$this->core_campaign->enable_target( $amount ),
				$this->slug,
			);
		} catch ( CoreInvalidCampaignTargetException $e ) {

			throw new InvalidCampaignTargetException( $e->getMessage(), previous: $e );
		} catch ( CoreCampaignChangeException $e ) {

			throw new CampaignChangeException( $e->getMessage(), previous: $e );
		}
	}

	/**
	 * Disables targeting (amount becomes zero).
	 *
	 * @since 1.0.0
	 *
	 * @return self The campaign with targeting disabled.
	 *
	 * @throws CampaignChangeException When targeting is already disabled.
	 */
	public function disable_target(): self {

		try {
			return new self(
				$this->core_campaign->disable_target(),
				$this->slug,
			);
		} catch ( CoreCampaignChangeException $e ) {

			throw new CampaignChangeException( $e->getMessage(), previous: $e );
		}
	}

	/**
	 * Sets the target amount.
	 *
	 * Amount 0 disables targeting, positive amount enables or updates it.
	 *
	 * @since 1.0.0
	 *
	 * @param int $amount The desired target amount in minor currency units (0 to disable; >0 to enable/update).
	 *
	 * @return self The campaign with updated targeting state.
	 *
	 * @throws InvalidCampaignTargetException When the amount is invalid (e.g., negative).
	 * @throws CampaignChangeException When the operation would not change the current state.
	 */
	public function set_target_amount( int $amount ): self {

		try {
			return new self(
				$this->core_campaign->set_target_amount( $amount ),
				$this->slug,
			);
		} catch ( CoreInvalidCampaignTargetException $e ) {

			throw new InvalidCampaignTargetException( $e->getMessage(), previous: $e );
		} catch ( CoreCampaignChangeException $e ) {

			throw new CampaignChangeException( $e->getMessage(), previous: $e );
		}
	}

	/**
	 * Changes the campaign slug.
	 *
	 * @since 1.0.0
	 *
	 * @param string|CampaignSlug $new_slug The new slug.
	 *
	 * @return self The campaign with updated slug.
	 *
	 * @throws InvalidCampaignSlugException When the provided slug is invalid.
	 * @throws CampaignChangeException When the slug matches the current one.
	 */
	public function change_slug( string|CampaignSlug $new_slug ): self {

		if ( is_string( $new_slug ) ) {
			$new_slug = CampaignSlug::create( $new_slug );
		}

		if ( $new_slug->equals( $this->slug ) ) {

			throw new CampaignChangeException(
				sprintf(
					'Campaign slug must be different from the current one. Given: "%s".',
					$new_slug->get_value(),
				),
			);
		}

		return new self( $this->core_campaign, $new_slug );
	}
}
