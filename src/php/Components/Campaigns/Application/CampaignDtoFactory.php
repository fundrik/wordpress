<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Components\Campaigns\Application;

use Fundrik\Core\Support\ArrayExtractor;
use Fundrik\Core\Support\Exceptions\ArrayExtractionException;
use Fundrik\WordPress\Components\Campaigns\Application\Exceptions\CampaignDtoFactoryException;
use Fundrik\WordPress\Components\Campaigns\Domain\Campaign;

/**
 * Creates WordPress-specific CampaignDto objects.
 *
 * @since 1.0.0
 */
final readonly class CampaignDtoFactory {

	/**
	 * Creates a WordPress CampaignDto from a raw associative array.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, int|string|bool> $data The input data array with keys:
	 *        - id (int): The campaign ID.
	 *        - title (string): The campaign title.
	 *        - slug (string): The campaign slug.
	 *        - is_active (bool): Whether the campaign is active.
	 *        - is_open (bool): Whether the campaign is open.
	 *        - has_target (bool): Whether the campaign has a target amount.
	 *        - target_amount (int): The campaign target amount.
	 *
	 * @phpstan-param array{
	 *   id: int,
	 *   title: string,
	 *   slug: string,
	 *   is_active: bool,
	 *   is_open: bool,
	 *   has_target: bool,
	 *   target_amount: int
	 * } $data
	 *
	 * @return CampaignDto The DTO constructed from array values.
	 */
	public function from_array( array $data ): CampaignDto {

		try {
			return new CampaignDto(
				id: ArrayExtractor::extract_id_int_required( $data, 'id' ),
				title: ArrayExtractor::extract_string_required( $data, 'title' ),
				slug: ArrayExtractor::extract_string_required( $data, 'slug' ),
				is_active: ArrayExtractor::extract_bool_required( $data, 'is_active' ),
				is_open: ArrayExtractor::extract_bool_required( $data, 'is_open' ),
				has_target: ArrayExtractor::extract_bool_required( $data, 'has_target' ),
				target_amount: ArrayExtractor::extract_int_required( $data, 'target_amount' ),
			);
		} catch ( ArrayExtractionException $e ) {

			throw new CampaignDtoFactoryException(
				'Failed to create CampaignDto from array: ' . $e->getMessage(),
				previous: $e,
			);
		}
	}

	/**
	 * Creates a WordPress CampaignDto from a WordPress campaign entity.
	 *
	 * @since 1.0.0
	 *
	 * @param Campaign $campaign The WordPress campaign entity.
	 *
	 * @return CampaignDto The DTO representation of the campaign.
	 */
	public function from_campaign( Campaign $campaign ): CampaignDto {

		return new CampaignDto(
			id: $campaign->get_id(),
			title: $campaign->get_title(),
			is_active: $campaign->is_active(),
			is_open: $campaign->is_open(),
			has_target: $campaign->has_target(),
			target_amount: $campaign->get_target_amount(),
			slug: $campaign->get_slug(),
		);
	}
}
