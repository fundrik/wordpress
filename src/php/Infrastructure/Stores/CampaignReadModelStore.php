<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Stores;

use Fundrik\WordPress\Infrastructure\Ports\Database\DatabaseExceptionInterface;
use Fundrik\WordPress\Infrastructure\Ports\Database\DatabasePort;
use Fundrik\WordPress\Infrastructure\Ports\Database\DatabaseRowNotFoundExceptionInterface;

/**
 * Provides campaign read model persistence operations.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class CampaignReadModelStore {

	private const string TABLE_NAME = 'fundrik_campaigns';

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param DatabasePort $database Writes campaign read model values.
	 */
	public function __construct(
		private DatabasePort $database,
	) {}

	/**
	 * Applies summary deltas to the campaign read model.
	 *
	 * @since 1.0.0
	 *
	 * @param int $campaign_id Campaign ID.
	 * @param int $collected_amount_delta Collected amount delta.
	 * @param int $donations_count_delta Donations count delta.
	 *
	 * @throws DatabaseRowNotFoundExceptionInterface When the row does not exist.
	 * @throws DatabaseExceptionInterface When the update fails.
	 */
	public function apply_summary_deltas(
		int $campaign_id,
		int $collected_amount_delta,
		int $donations_count_delta,
	): void {

		$this->database->apply_numeric_deltas(
			self::TABLE_NAME,
			$campaign_id,
			[
				'collected_amount' => $collected_amount_delta,
				'donations_count' => $donations_count_delta,
			],
		);
	}

	/**
	 * Updates summary values in the campaign read model.
	 *
	 * @since 1.0.0
	 *
	 * @param int $campaign_id Campaign ID.
	 * @param int $collected_amount Collected amount.
	 * @param int $donations_count Donations count.
	 *
	 * @throws DatabaseExceptionInterface When the update fails.
	 */
	public function update_summary( int $campaign_id, int $collected_amount, int $donations_count ): void {

		$this->database->update(
			self::TABLE_NAME,
			[
				'collected_amount' => $collected_amount,
				'donations_count' => $donations_count,
			],
			[
				'id' => $campaign_id,
			],
		);
	}
}
