<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Renderers\DonationForm;

/**
 * Represents render-ready data for the donation form markup.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class DonationFormRenderData {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param int $campaign_id Campaign ID.
	 * @param string $rest_url Donation form REST URL.
	 * @param int $default_amount Default donation amount.
	 * @param string $amount_label Visible amount label.
	 */
	public function __construct(
		public int $campaign_id,
		public string $rest_url,
		public int $default_amount,
		public string $amount_label,
	) {}
}
