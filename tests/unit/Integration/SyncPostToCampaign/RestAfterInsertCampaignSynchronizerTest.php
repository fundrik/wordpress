<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\SyncPostToCampaign;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositorySaveOutcome;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositorySaveResult;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Campaigns\Domain\CampaignFactory;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;
use Fundrik\WordPress\Integration\SyncPostToCampaign\RestAfterInsertCampaignSynchronizer;
use Fundrik\WordPress\Integration\SyncPostToCampaign\RestCampaignSyncDataDto;
use Fundrik\WordPress\Tests\Fixtures\FakeCampaignRepositoryException;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( RestAfterInsertCampaignSynchronizer::class )]
#[UsesClass( RestCampaignSyncDataDto::class )]
final class RestAfterInsertCampaignSynchronizerTest extends MockeryTestCase {

	private CampaignFactory $campaign_factory;
	private CampaignRepositoryPort&MockInterface $campaign_repository;

	private RestAfterInsertCampaignSynchronizer $synchronizer;

	protected function setUp(): void {

		parent::setUp();

		$this->campaign_factory = new CampaignFactory();
		$this->campaign_repository = Mockery::mock( CampaignRepositoryPort::class );

		$this->synchronizer = new RestAfterInsertCampaignSynchronizer(
			$this->campaign_factory,
			$this->campaign_repository,
		);
	}

	#[Test]
	public function sync_uses_persisted_version_as_expected_version_and_saves(): void {

		$data = new RestCampaignSyncDataDto(
			id: EntityId::create( 10 ),
			title: 'Title',
			version: EntityVersion::create( 999 ), // Should NOT be used as expected_version here.
			is_active: false,
			is_open: true,
			has_target: false,
			target_amount: 0,
			target_currency: 'USD',
		);

		$persisted = $this->campaign_factory->create_from_primitives(
			id: 10,
			version: 5,
			title: 'Persisted',
			is_active: true,
			is_open: true,
			has_target: false,
			target_amount: 0,
			target_currency: 'USD',
		);

		$this->campaign_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->with( Mockery::type( EntityId::class ) )
			->andReturn( $persisted );

		$this->campaign_repository
			->shouldReceive( 'save' )
			->once()
			->with( Mockery::type( Campaign::class ) )
			->andReturnUsing(
				static function ( Campaign $campaign ): CampaignRepositorySaveOutcome {

					self::assertSame( 10, $campaign->get_id()->get_value() );
					self::assertSame( 5, $campaign->get_version()->get_value() );
					self::assertSame( 'Title', $campaign->get_title() );
					self::assertFalse( $campaign->is_active() );
					self::assertTrue( $campaign->is_open() );
					self::assertFalse( $campaign->has_target() );
					self::assertSame( 0, $campaign->get_target_money()->get_amount_minor() );
					self::assertSame( 'USD', $campaign->get_target_money()->get_currency() );

					return new CampaignRepositorySaveOutcome(
						result: CampaignRepositorySaveResult::Updated,
						campaign: $campaign,
					);
				},
			);

		$this->synchronizer->sync( $data );
	}

	#[Test]
	public function sync_uses_initial_version_when_repository_lookup_throws(): void {

		$data = new RestCampaignSyncDataDto(
			id: EntityId::create( 10 ),
			title: 'Title',
			version: EntityVersion::create( 3 ),
			is_active: true,
			is_open: true,
			has_target: false,
			target_amount: 0,
			target_currency: 'EUR',
		);

		$this->campaign_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->andThrow( new FakeCampaignRepositoryException( 'DB failed.' ) );

		$this->campaign_repository
			->shouldReceive( 'save' )
			->once()
			->with( Mockery::type( Campaign::class ) )
			->andReturnUsing(
				static function ( Campaign $campaign ): CampaignRepositorySaveOutcome {

					self::assertSame( 10, $campaign->get_id()->get_value() );
					self::assertSame( 1, $campaign->get_version()->get_value() );
					self::assertSame( 'Title', $campaign->get_title() );
					self::assertTrue( $campaign->is_active() );
					self::assertTrue( $campaign->is_open() );
					self::assertFalse( $campaign->has_target() );
					self::assertSame( 0, $campaign->get_target_money()->get_amount_minor() );
					self::assertSame( 'EUR', $campaign->get_target_money()->get_currency() );

					return new CampaignRepositorySaveOutcome(
						result: CampaignRepositorySaveResult::Inserted,
						campaign: $campaign,
					);
				},
			);

		$this->synchronizer->sync( $data );
	}

	#[Test]
	public function sync_uses_initial_version_when_campaign_is_not_found(): void {

		$data = new RestCampaignSyncDataDto(
			id: EntityId::create( 10 ),
			title: 'Title',
			version: EntityVersion::create( 3 ),
			is_active: true,
			is_open: true,
			has_target: false,
			target_amount: 0,
			target_currency: 'RUB',
		);

		$this->campaign_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->andReturn( null );

		$this->campaign_repository
			->shouldReceive( 'save' )
			->once()
			->with( Mockery::type( Campaign::class ) )
			->andReturnUsing(
				static function ( Campaign $campaign ): CampaignRepositorySaveOutcome {

					self::assertSame( 10, $campaign->get_id()->get_value() );
					self::assertSame( 1, $campaign->get_version()->get_value() );
					self::assertSame( 'Title', $campaign->get_title() );
					self::assertTrue( $campaign->is_active() );
					self::assertTrue( $campaign->is_open() );
					self::assertFalse( $campaign->has_target() );
					self::assertSame( 0, $campaign->get_target_money()->get_amount_minor() );
					self::assertSame( 'RUB', $campaign->get_target_money()->get_currency() );

					return new CampaignRepositorySaveOutcome(
						result: CampaignRepositorySaveResult::Inserted,
						campaign: $campaign,
					);
				},
			);

		$this->synchronizer->sync( $data );
	}
}
