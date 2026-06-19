<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\ReadModels;

use Fundrik\Core\Components\Campaigns\Application\ReadModels\Campaign as CoreCampaign;
use Fundrik\Core\Components\Shared\Domain\UtcDateTime;

/**
 * Represents the public WordPress-facing campaign read model.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class Campaign {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param CoreCampaign $core_campaign Core campaign read model.
	 * @param string|null $permalink Campaign permalink, if available.
	 * @param int|null $featured_image_id Featured image attachment ID, if available.
	 */
	public function __construct(
		private CoreCampaign $core_campaign,
		private ?string $permalink,
		private ?int $featured_image_id,
	) {}

	/**
	 * Returns the underlying core campaign read model.
	 *
	 * @since 1.0.0
	 *
	 * @return CoreCampaign Underlying core campaign read model.
	 */
	public function get_core_campaign(): CoreCampaign {

		return $this->core_campaign;
	}

	/**
	 * Returns the campaign ID.
	 *
	 * @since 1.0.0
	 *
	 * @return int Campaign ID.
	 */
	public function get_id(): int {

		return $this->core_campaign->get_id();
	}

	/**
	 * Returns the campaign title.
	 *
	 * @since 1.0.0
	 *
	 * @return string Campaign title.
	 */
	public function get_title(): string {

		return $this->core_campaign->get_title();
	}

	/**
	 * Returns whether the campaign accepts donations.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True when the campaign accepts donations.
	 */
	public function accepts_donations(): bool {

		return $this->core_campaign->accepts_donations();
	}

	/**
	 * Returns the campaign currency code.
	 *
	 * @since 1.0.0
	 *
	 * @return string Currency code.
	 */
	public function get_currency_code(): string {

		return $this->core_campaign->get_currency_code();
	}

	/**
	 * Returns the campaign target amount.
	 *
	 * @since 1.0.0
	 *
	 * @return int|null Target amount, if configured.
	 */
	public function get_target_amount(): ?int {

		return $this->core_campaign->get_target_amount();
	}

	/**
	 * Returns the collected amount.
	 *
	 * @since 1.0.0
	 *
	 * @return int Collected amount in minor units.
	 */
	public function get_collected_amount(): int {

		return $this->core_campaign->get_collected_amount();
	}

	/**
	 * Returns the donations count.
	 *
	 * @since 1.0.0
	 *
	 * @return int Donations count.
	 */
	public function get_donations_count(): int {

		return $this->core_campaign->get_donations_count();
	}

	/**
	 * Returns the campaign creation timestamp.
	 *
	 * @since 1.0.0
	 *
	 * @return UtcDateTime Creation timestamp.
	 */
	public function get_created_at(): UtcDateTime {

		return $this->core_campaign->get_created_at();
	}

	/**
	 * Returns the campaign update timestamp.
	 *
	 * @since 1.0.0
	 *
	 * @return UtcDateTime|null Update timestamp, null otherwise.
	 */
	public function get_updated_at(): ?UtcDateTime {

		return $this->core_campaign->get_updated_at();
	}

	/**
	 * Returns the campaign permalink.
	 *
	 * @since 1.0.0
	 *
	 * @return string|null Campaign permalink, null otherwise.
	 */
	public function get_permalink(): ?string {

		return $this->permalink;
	}

	/**
	 * Returns the featured image attachment ID.
	 *
	 * @since 1.0.0
	 *
	 * @return int|null Featured image attachment ID, null otherwise.
	 */
	public function get_featured_image_id(): ?int {

		return $this->featured_image_id;
	}
}
