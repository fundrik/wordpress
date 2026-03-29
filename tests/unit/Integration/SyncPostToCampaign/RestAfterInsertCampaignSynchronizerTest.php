<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\SyncPostToCampaign;

use Brain\Monkey\Functions;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Application\Services\CampaignCommandService;
use Fundrik\Core\Components\Campaigns\Application\UseCases\ChangeCampaignTarget\ChangeCampaignTargetHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\DisableCampaignDonations\DisableCampaignDonationsHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\CreateCampaign\CreateCampaignHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\DeleteCampaign\DeleteCampaignHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\EnableCampaignDonations\EnableCampaignDonationsHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\RenameCampaign\RenameCampaignHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\SyncCampaignFromSnapshot\SyncCampaignFromSnapshotHandler;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Campaigns\Domain\CampaignFactory;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryPort;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusPort;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;
use Fundrik\WordPress\Integration\PostTypes\Configs\CampaignPostTypeConfig;
use Fundrik\WordPress\Integration\SyncPostToCampaign\RestAfterInsertCampaignSynchronizer;
use Fundrik\WordPress\Integration\SyncPostToCampaign\RestCampaignSyncData;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( RestAfterInsertCampaignSynchronizer::class )]
#[UsesClass( RestCampaignSyncData::class )]
final class RestAfterInsertCampaignSynchronizerTest extends MockeryTestCase {

	private CampaignFactory $campaign_factory;
	private CampaignRepositoryPort&MockInterface $campaign_repository;
	private DonationRepositoryPort&MockInterface $donation_repository;
	private ApplicationEventBusPort&MockInterface $event_bus;

	private RestAfterInsertCampaignSynchronizer $synchronizer;

	protected function setUp(): void {

		parent::setUp();

		$this->campaign_factory = new CampaignFactory();
		$this->campaign_repository = Mockery::mock( CampaignRepositoryPort::class );
		$this->donation_repository = Mockery::mock( DonationRepositoryPort::class );
		$this->event_bus = Mockery::mock( ApplicationEventBusPort::class );

		$this->synchronizer = new RestAfterInsertCampaignSynchronizer(
			self::new_campaign_command_service(
				$this->campaign_repository,
				$this->donation_repository,
				$this->event_bus,
			),
			$this->campaign_repository,
		);
	}

	#[Test]
	public function sync_creates_campaign_when_post_is_being_created(): void {

		$data = new RestCampaignSyncData(
			id: EntityId::create( 10 ),
			title: 'Title',
			version: EntityVersion::create( 999 ),
			accepts_donations: true,
			has_target: false,
			target_amount: null,
			target_currency: 'USD',
		);

		$this->campaign_repository
			->shouldReceive( 'exists_by_id' )
			->once()
			->with( Mockery::type( EntityId::class ) )
			->andReturn( false );
		$this->campaign_repository->shouldNotReceive( 'update' );
		Functions\expect( 'delete_post_meta' )
			->once()
			->with( 10, CampaignPostTypeConfig::META_TARGET_AMOUNT );

		$this->campaign_repository
			->shouldReceive( 'insert' )
			->once()
			->with(
				Mockery::on(
					static fn ( Campaign $campaign ): bool => $campaign->get_id()->get_value() === 10
						&& $campaign->get_version()->get_value() === 1
						&& $campaign->get_title() === 'Title'
						&& $campaign->accepts_donations()
						&& ! $campaign->has_target()
						&& $campaign->get_target()->get_currency()->get_code() === 'USD',
				),
			)
			->andReturnUsing( static fn ( Campaign $campaign ): Campaign => $campaign );

		$this->event_bus->shouldReceive( 'publish' )->once();

		$this->synchronizer->sync( $data );
	}

	#[Test]
	public function sync_updates_campaign_from_snapshot_when_post_is_being_updated(): void {

		$data = new RestCampaignSyncData(
			id: EntityId::create( 15 ),
			title: 'Updated title',
			version: EntityVersion::create( 7 ),
			accepts_donations: false,
			has_target: true,
			target_amount: 1_500,
			target_currency: 'EUR',
		);

		$persisted = $this->campaign_factory->create_from_primitives(
			id: 15,
			version: 6,
			title: 'Persisted title',
			accepts_donations: true,
			currency_code: 'EUR',
			target_amount: null,
		);

		$this->campaign_repository
			->shouldReceive( 'exists_by_id' )
			->once()
			->with( Mockery::type( EntityId::class ) )
			->andReturn( true );
		$this->campaign_repository->shouldNotReceive( 'insert' );
		Functions\expect( 'delete_post_meta' )->never();

		$this->campaign_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->with( Mockery::type( EntityId::class ) )
			->andReturn( $persisted );

		$this->campaign_repository
			->shouldReceive( 'update' )
			->once()
			->with(
				Mockery::on(
					static fn ( Campaign $campaign ): bool => $campaign->get_id()->get_value() === 15
						&& $campaign->get_version()->get_value() === 7
						&& $campaign->get_title() === 'Updated title'
						&& ! $campaign->accepts_donations()
						&& $campaign->has_target()
						&& $campaign->get_target()->get_amount()?->get_value() === 1_500
						&& $campaign->get_target()->get_currency()->get_code() === 'EUR',
				),
			)
			->andReturnUsing( static fn ( Campaign $campaign ): Campaign => $campaign );

		$this->event_bus->shouldReceive( 'publish' )->once();

		$this->synchronizer->sync( $data );
	}

	#[Test]
	public function sync_updates_campaign_and_clears_target_amount_meta_when_target_is_disabled(): void {

		$data = new RestCampaignSyncData(
			id: EntityId::create( 15 ),
			title: 'Updated title',
			version: EntityVersion::create( 7 ),
			accepts_donations: false,
			has_target: false,
			target_amount: null,
			target_currency: 'EUR',
		);

		$persisted = $this->campaign_factory->create_from_primitives(
			id: 15,
			version: 6,
			title: 'Persisted title',
			accepts_donations: true,
			currency_code: 'EUR',
			target_amount: 1_500,
		);

		$this->campaign_repository
			->shouldReceive( 'exists_by_id' )
			->once()
			->with( Mockery::type( EntityId::class ) )
			->andReturn( true );
		$this->campaign_repository->shouldNotReceive( 'insert' );

		$this->campaign_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->with( Mockery::type( EntityId::class ) )
			->andReturn( $persisted );

		$this->campaign_repository
			->shouldReceive( 'update' )
			->once()
			->with(
				Mockery::on(
					static fn ( Campaign $campaign ): bool => $campaign->get_id()->get_value() === 15
						&& $campaign->get_version()->get_value() === 7
						&& $campaign->get_title() === 'Updated title'
						&& ! $campaign->accepts_donations()
						&& ! $campaign->has_target()
						&& $campaign->get_target()->get_amount() === null
						&& $campaign->get_target()->get_currency()->get_code() === 'EUR',
				),
			)
			->andReturnUsing( static fn ( Campaign $campaign ): Campaign => $campaign );

		Functions\expect( 'delete_post_meta' )
			->once()
			->with( 15, CampaignPostTypeConfig::META_TARGET_AMOUNT );

		$this->event_bus->shouldReceive( 'publish' )->once();

		$this->synchronizer->sync( $data );
	}

	#[Test]
	public function sync_creates_campaign_when_snapshot_campaign_is_missing(): void {

		$data = new RestCampaignSyncData(
			id: EntityId::create( 15 ),
			title: 'Created from snapshot',
			version: EntityVersion::create( 7 ),
			accepts_donations: false,
			has_target: false,
			target_amount: null,
			target_currency: 'EUR',
		);

		$this->campaign_repository
			->shouldReceive( 'exists_by_id' )
			->once()
			->with( Mockery::type( EntityId::class ) )
			->andReturn( false );
		$this->campaign_repository->shouldNotReceive( 'update' );

		$this->campaign_repository
			->shouldReceive( 'insert' )
			->once()
			->with(
				Mockery::on(
					static fn ( Campaign $campaign ): bool => $campaign->get_id()->get_value() === 15
						&& $campaign->get_version()->get_value() === 1
						&& $campaign->get_title() === 'Created from snapshot'
						&& ! $campaign->accepts_donations()
						&& ! $campaign->has_target()
						&& $campaign->get_target()->get_amount() === null
						&& $campaign->get_target()->get_currency()->get_code() === 'EUR',
				),
			)
			->andReturnUsing( static fn ( Campaign $campaign ): Campaign => $campaign );

		Functions\expect( 'delete_post_meta' )
			->once()
			->with( 15, CampaignPostTypeConfig::META_TARGET_AMOUNT );

		$this->event_bus->shouldReceive( 'publish' )->once();

		$this->synchronizer->sync( $data );
	}

	#[Test]
	private static function new_campaign_command_service(
		CampaignRepositoryPort $campaign_repository,
		DonationRepositoryPort $donation_repository,
		ApplicationEventBusPort $event_bus,
	): CampaignCommandService {

		return new CampaignCommandService(
			new CreateCampaignHandler( $campaign_repository, $event_bus ),
			new CampaignFactory(),
			new SyncCampaignFromSnapshotHandler( $campaign_repository, $event_bus ),
			new RenameCampaignHandler( $campaign_repository, $event_bus ),
			new EnableCampaignDonationsHandler( $campaign_repository, $event_bus ),
			new DisableCampaignDonationsHandler( $campaign_repository, $event_bus ),
			new ChangeCampaignTargetHandler( $campaign_repository, $event_bus ),
			new DeleteCampaignHandler( $campaign_repository, $donation_repository, $event_bus ),
		);
	}
}


