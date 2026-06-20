<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\ReadModels;

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
	 * @param int $id Campaign ID.
	 * @param string $title Campaign title.
	 * @param string|null $permalink Campaign permalink, if available.
	 * @param int|null $featured_image_id Featured image attachment ID, if available.
	 * @param bool $accepts_donations Whether the campaign accepts donations.
	 * @param string $currency_code Campaign currency code.
	 * @param int|null $target_amount Target amount, if configured.
	 * @param int $collected_amount Collected amount.
	 * @param int $donations_count Donations count.
	 * @param UtcDateTime $created_at Creation timestamp.
	 * @param UtcDateTime|null $updated_at Update timestamp, null otherwise.
	 */
	public function __construct(
		private int $id,
		private string $title,
		private ?string $permalink,
		private ?int $featured_image_id,
		private bool $accepts_donations,
		private string $currency_code,
		private ?int $target_amount,
		private int $collected_amount,
		private int $donations_count,
		private UtcDateTime $created_at,
		private ?UtcDateTime $updated_at,
	) {}

	/**
	 * Returns the campaign ID.
	 *
	 * @since 1.0.0
	 *
	 * @return int Campaign ID.
	 */
	public function get_id(): int {

		return $this->id;
	}

	/**
	 * Returns the campaign title.
	 *
	 * @since 1.0.0
	 *
	 * @return string Campaign title.
	 */
	public function get_title(): string {

		return $this->title;
	}

	/**
	 * Returns whether the campaign accepts donations.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True when the campaign accepts donations.
	 */
	public function accepts_donations(): bool {

		return $this->accepts_donations;
	}

	/**
	 * Returns the campaign currency code.
	 *
	 * @since 1.0.0
	 *
	 * @return string Currency code.
	 */
	public function get_currency_code(): string {

		return $this->currency_code;
	}

	/**
	 * Returns whether the campaign has a configured target.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True when a target is configured.
	 */
	public function has_target(): bool {

		return $this->target_amount !== null;
	}

	/**
	 * Returns the target amount.
	 *
	 * @since 1.0.0
	 *
	 * @return int|null Target amount, if configured.
	 */
	public function get_target_amount(): ?int {

		return $this->target_amount;
	}

	/**
	 * Returns the collected amount.
	 *
	 * @since 1.0.0
	 *
	 * @return int Collected amount in minor units.
	 */
	public function get_collected_amount(): int {

		return $this->collected_amount;
	}

	/**
	 * Returns the donations count.
	 *
	 * @since 1.0.0
	 *
	 * @return int Donations count.
	 */
	public function get_donations_count(): int {

		return $this->donations_count;
	}

	/**
	 * Returns the creation timestamp.
	 *
	 * @since 1.0.0
	 *
	 * @return UtcDateTime Creation timestamp.
	 */
	public function get_created_at(): UtcDateTime {

		return $this->created_at;
	}

	/**
	 * Returns the last update timestamp.
	 *
	 * @since 1.0.0
	 *
	 * @return UtcDateTime|null Update timestamp, null otherwise.
	 */
	public function get_updated_at(): ?UtcDateTime {

		return $this->updated_at;
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
