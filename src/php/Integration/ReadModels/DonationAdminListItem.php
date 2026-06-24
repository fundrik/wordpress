<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\ReadModels;

/**
 * Represents a donation row for the admin donations list.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class DonationAdminListItem {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id Donation ID.
	 * @param string $campaign_title Campaign title.
	 * @param string $campaign_edit_url Campaign edit URL.
	 * @param string $amount Formatted amount label.
	 * @param string $status Donation status.
	 * @param string $created_at Formatted creation timestamp.
	 * @param string|null $updated_at Formatted update timestamp, null otherwise.
	 */
	public function __construct(
		private string $id,
		private string $campaign_title,
		private string $campaign_edit_url,
		private string $amount,
		private string $status,
		private string $created_at,
		private ?string $updated_at,
	) {}

	/**
	 * Returns the donation ID.
	 *
	 * @since 1.0.0
	 *
	 * @return string Donation ID.
	 */
	public function get_id(): string {

		return $this->id;
	}

	/**
	 * Returns the campaign title.
	 *
	 * @since 1.0.0
	 *
	 * @return string Campaign title.
	 */
	public function get_campaign_title(): string {

		return $this->campaign_title;
	}

	/**
	 * Returns the campaign edit URL.
	 *
	 * @since 1.0.0
	 *
	 * @return string Campaign edit URL.
	 */
	public function get_campaign_edit_url(): string {

		return $this->campaign_edit_url;
	}

	/**
	 * Returns the formatted amount label.
	 *
	 * @since 1.0.0
	 *
	 * @return string Amount label.
	 */
	public function get_amount(): string {

		return $this->amount;
	}

	/**
	 * Returns the donation status.
	 *
	 * @since 1.0.0
	 *
	 * @return string Donation status.
	 */
	public function get_status(): string {

		return $this->status;
	}

	/**
	 * Returns the formatted creation timestamp.
	 *
	 * @since 1.0.0
	 *
	 * @return string Creation timestamp.
	 */
	public function get_created_at(): string {

		return $this->created_at;
	}

	/**
	 * Returns the formatted update timestamp.
	 *
	 * @since 1.0.0
	 *
	 * @return string|null Update timestamp, null otherwise.
	 */
	public function get_updated_at(): ?string {

		return $this->updated_at;
	}
}
