<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Components\Campaigns\Application;

use Fundrik\Core\Components\Campaigns\Application\CampaignAssembler as CoreCampaignAssembler;
use Fundrik\Core\Components\Campaigns\Application\CampaignDtoFactory as CoreCampaignDtoFactory;
use Fundrik\Core\Components\Campaigns\Application\Exceptions\CampaignAssemblerException as CoreCampaignAssemblerException;
use Fundrik\Core\Components\Campaigns\Application\Exceptions\CampaignDtoFactoryException as CoreCampaignDtoFactoryException;
use Fundrik\WordPress\Components\Campaigns\Application\Exceptions\CampaignAssemblerException;
use Fundrik\WordPress\Components\Campaigns\Domain\Campaign;
use Fundrik\WordPress\Components\Campaigns\Domain\CampaignSlug;
use Fundrik\WordPress\Components\Campaigns\Domain\Exceptions\InvalidCampaignSlugException;

/**
 * Assembles WordPress-specific Campaign domain entities from DTO.
 *
 * @since 1.0.0
 */
final readonly class CampaignAssembler {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param CoreCampaignDtoFactory $core_dto_factory Builds validated DTOs for the core Campaign.
	 * @param CoreCampaignAssembler $core_assembler Assembles the core Campaign entity from the DTO.
	 */
	public function __construct(
		private CoreCampaignDtoFactory $core_dto_factory,
		private CoreCampaignAssembler $core_assembler,
	) {}

	/**
	 * Creates a WordPress Campaign entity from a DTO.
	 *
	 * @since 1.0.0
	 *
	 * @param CampaignDto $dto The DTO representing campaign data.
	 *
	 * @return Campaign The domain entity with WordPress-specific data constructed from the DTO.
	 *
	 * @throws CampaignAssemblerException When the DTO contains invalid data.
	 */
	public function from_dto( CampaignDto $dto ): Campaign {

		try {
			$core_dto = $this->core_dto_factory->from_array(
				[
					'id' => $dto->id,
					'title' => $dto->title,
					'is_active' => $dto->is_active,
					'is_open' => $dto->is_open,
					'has_target' => $dto->has_target,
					'target_amount' => $dto->target_amount,
				],
			);

			$core_campaign = $this->core_assembler->from_dto( $core_dto );

			$slug = CampaignSlug::create( $dto->slug );

			return new Campaign( core_campaign: $core_campaign, slug: $slug );

		} catch ( CoreCampaignDtoFactoryException | CoreCampaignAssemblerException | InvalidCampaignSlugException $e ) {

			throw new CampaignAssemblerException(
				sprintf( 'Cannot assemble Campaign from DTO: %s', $e->getMessage() ),
				previous: $e,
			);
		}
	}
}
