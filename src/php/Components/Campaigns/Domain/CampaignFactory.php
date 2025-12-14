<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Components\Campaigns\Domain;

use Fundrik\Core\Components\Campaigns\Domain\CampaignFactory as CoreCampaignFactory;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTitle;
use Fundrik\Core\Components\Campaigns\Domain\Exceptions\CampaignFactoryException as CoreCampaignFactoryException;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\WordPress\Components\Campaigns\Domain\Exceptions\CampaignFactoryException;
use Fundrik\WordPress\Components\Campaigns\Domain\Exceptions\InvalidCampaignIdException;
use Fundrik\WordPress\Components\Campaigns\Domain\Exceptions\InvalidCampaignSlugException;

/**
 * Creates WordPress Campaign entities from primitives and value objects.
 *
 * @since 1.0.0
 */
final readonly class CampaignFactory {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param CoreCampaignFactory $core_campaign_factory Creates core Campaign domain entities.
	 */
	public function __construct(
		private CoreCampaignFactory $core_campaign_factory,
	) {}

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
	/**
	 * Creates a WordPress Campaign from primitive values.
	 *
	 * @since 1.0.0
	 *
	 * @param int|EntityId $id The campaign ID.
	 * @param string|CampaignTitle $title The campaign title.
	 * @param string|CampaignSlug $slug The campaign slug.
	 * @param bool $is_active Whether the campaign is active.
	 * @param bool $is_open Whether the campaign is open.
	 * @param bool $has_target Whether targeting is enabled.
	 * @param int $target_amount The target amount in minor units (0 if disabled).
	 *
	 * @return Campaign The built WordPress campaign entity.
	 *
	 * @throws CampaignFactoryException When the campaign cannot be created from the given input values.
	 */
	public function create(
		int|EntityId $id,
		string|CampaignTitle $title,
		string|CampaignSlug $slug,
		bool $is_active,
		bool $is_open,
		bool $has_target,
		int $target_amount,
	): Campaign {

		try {

			$slug = is_string( $slug ) ? CampaignSlug::create( $slug ) : $slug;

			$core_campaign = $this->core_campaign_factory->create(
				id: $id,
				title: $title,
				is_active: $is_active,
				is_open: $is_open,
				has_target: $has_target,
				target_amount: $target_amount,
			);

			return new Campaign( core_campaign: $core_campaign, slug: $slug );

		} catch (
			CoreCampaignFactoryException
			| InvalidCampaignIdException
			| InvalidCampaignSlugException $e
		) {

			throw new CampaignFactoryException(
				sprintf( 'Cannot create Campaign: %s', $e->getMessage() ),
				previous: $e,
			);
		}
	}
	// phpcs:enable
}
