<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Components\Campaigns\Application;

/**
 * Carries WordPress campaign data between infrastructure and domain layers.
 *
 * @since 1.0.0
 */
final readonly class CampaignDto {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id The campaign ID.
	 * @param string $title The campaign title.
	 * @param string $slug The campaign slug.
	 * @param bool $is_active Whether the campaign is active.
	 * @param bool $is_open Whether the campaign is open.
	 * @param bool $has_target Whether the campaign has a target amount.
	 * @param int $target_amount The target amount in minor currency units, must be >= 0 when has_target is true.
	 */
	public function __construct(
		public int $id,
		public string $title,
		public string $slug,
		public bool $is_active,
		public bool $is_open,
		public bool $has_target,
		public int $target_amount,
	) {
	}

	/**
	 * Returns the DTO as an associative array.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, int|string|bool> The associative array representation.
	 */
	public function to_array(): array {

		return [
			'id' => $this->id,
			'title' => $this->title,
			'slug' => $this->slug,
			'is_active' => $this->is_active,
			'is_open' => $this->is_open,
			'has_target' => $this->has_target,
			'target_amount' => $this->target_amount,
		];
	}
}
