<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Boot\Units;

use Brain\Monkey\Functions;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Application\Services\CampaignCommandService;
use Fundrik\Core\Components\Campaigns\Application\UseCases\ChangeCampaignTarget\ChangeCampaignTargetHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\CreateCampaign\CreateCampaignHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\DeleteCampaign\DeleteCampaignException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\DeleteCampaign\DeleteCampaignHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\DisableCampaignDonations\DisableCampaignDonationsHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\EnableCampaignDonations\EnableCampaignDonationsHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\FindCampaignById\FindCampaignByIdException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\FindCampaignById\FindCampaignByIdHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\RenameCampaign\RenameCampaignHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\SyncCampaignFromSnapshot\SyncCampaignFromSnapshotHandler;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Campaigns\Domain\CampaignFactory;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryPort;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusPort;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\WordPress\Components\Campaigns\Domain\CampaignId;
use Fundrik\WordPress\Infrastructure\Logger;
use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsReader;
use Fundrik\WordPress\Integration\AdminSettings\Groups\CampaignSettingsGroup;
use Fundrik\WordPress\Integration\AdminSettings\Groups\GeneralSettingsGroup;
use Fundrik\WordPress\Integration\AdminSettings\Settings\Campaign\CampaignDefaultAcceptsDonationsSetting;
use Fundrik\WordPress\Integration\AdminSettings\Settings\Campaign\CampaignDefaultHasTargetSetting;
use Fundrik\WordPress\Integration\AdminSettings\Settings\General\CurrencySetting;
use Fundrik\WordPress\Integration\Boot\BootUnitLogger;
use Fundrik\WordPress\Integration\Boot\Units\SyncPostToCampaignBootUnit;
use Fundrik\WordPress\Integration\Helpers\CurrentAdminScreen;
use Fundrik\WordPress\Integration\Helpers\MetaReader;
use Fundrik\WordPress\Integration\Helpers\OptionReader;
use Fundrik\WordPress\Integration\Helpers\PluginUrl;
use Fundrik\WordPress\Integration\Helpers\SettingOptionName;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\DeletePostActionHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\EnqueueBlockEditorAssetsActionHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\RestAfterInsertCampaignActionHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\RestPreInsertCampaignFilterHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\RestPrepareCampaignFilterHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherLogger;
use Fundrik\WordPress\Integration\PostTypes\Configs\CampaignPostTypeConfig;
use Fundrik\WordPress\Integration\SyncPostToCampaign\RestAfterInsertCampaignSyncDataExtractor;
use Fundrik\WordPress\Integration\SyncPostToCampaign\RestAfterInsertCampaignSynchronizer;
use Fundrik\WordPress\Integration\SyncPostToCampaign\RestCampaignSyncData;
use Fundrik\WordPress\Integration\SyncPostToCampaign\RestPreInsertCampaignSyncDataExtractor;
use Fundrik\WordPress\Integration\SyncPostToCampaign\RestPreInsertCampaignSyncDataValidator;
use Fundrik\WordPress\Tests\Fixtures\FakeCampaignRepositoryException;
use Fundrik\WordPress\Tests\Fixtures\FakeStorage;
use Fundrik\WordPress\Tests\Fixtures\WordPress\WpPost;
use Fundrik\WordPress\Tests\Fixtures\WordPress\WpRestRequest;
use Fundrik\WordPress\Tests\Fixtures\WordPress\WpRestResponse;
use Fundrik\WordPress\Tests\Fixtures\WordPress\WpScreen;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use Mockery\Matcher\Closure as ClosureMatcher;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use Psr\Log\LoggerInterface;
use stdClass;
use WP_Error;

#[CoversClass( SyncPostToCampaignBootUnit::class )]
#[UsesClass( CampaignId::class )]
#[UsesClass( Logger::class )]
#[UsesClass( AdminSettingsReader::class )]
#[UsesClass( CampaignSettingsGroup::class )]
#[UsesClass( GeneralSettingsGroup::class )]
#[UsesClass( CampaignDefaultAcceptsDonationsSetting::class )]
#[UsesClass( CampaignDefaultHasTargetSetting::class )]
#[UsesClass( CurrencySetting::class )]
#[UsesClass( BootUnitLogger::class )]
#[UsesClass( CurrentAdminScreen::class )]
#[UsesClass( MetaReader::class )]
#[UsesClass( OptionReader::class )]
#[UsesClass( PluginUrl::class )]
#[UsesClass( SettingOptionName::class )]
#[UsesClass( DeletePostActionHookDispatcher::class )]
#[UsesClass( EnqueueBlockEditorAssetsActionHookDispatcher::class )]
#[UsesClass( RestAfterInsertCampaignActionHookDispatcher::class )]
#[UsesClass( RestPreInsertCampaignFilterHookDispatcher::class )]
#[UsesClass( RestPrepareCampaignFilterHookDispatcher::class )]
#[UsesClass( HookDispatcherLogger::class )]
#[UsesClass( CampaignPostTypeConfig::class )]
#[UsesClass( RestAfterInsertCampaignSyncDataExtractor::class )]
#[UsesClass( RestAfterInsertCampaignSynchronizer::class )]
#[UsesClass( RestCampaignSyncData::class )]
#[UsesClass( RestPreInsertCampaignSyncDataExtractor::class )]
#[UsesClass( RestPreInsertCampaignSyncDataValidator::class )]
final class SyncPostToCampaignBootUnitTest extends WordPressTestCase {

	private CampaignRepositoryPort&MockInterface $campaign_repository;
	private DonationRepositoryPort&MockInterface $donation_repository;
	private ApplicationEventBusPort&MockInterface $event_bus;
	private LoggerInterface&MockInterface $psr_logger;

	private RestPreInsertCampaignFilterHookDispatcher $rest_pre_insert_hook;
	private RestPrepareCampaignFilterHookDispatcher $rest_prepare_hook;
	private EnqueueBlockEditorAssetsActionHookDispatcher $enqueue_block_editor_assets_hook;
	private RestAfterInsertCampaignActionHookDispatcher $rest_after_insert_hook;
	private DeletePostActionHookDispatcher $delete_post_hook;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->psr_logger = Mockery::mock( LoggerInterface::class );
		$this->campaign_repository = Mockery::mock( CampaignRepositoryPort::class );
		$this->donation_repository = Mockery::mock( DonationRepositoryPort::class );
		$this->event_bus = Mockery::mock( ApplicationEventBusPort::class );

		$this->rest_pre_insert_hook = new RestPreInsertCampaignFilterHookDispatcher( $this->make_hook_logger() );
		$this->rest_prepare_hook = new RestPrepareCampaignFilterHookDispatcher( $this->make_hook_logger() );
		$this->enqueue_block_editor_assets_hook = new EnqueueBlockEditorAssetsActionHookDispatcher(
			$this->make_hook_logger(),
		);
		$this->rest_after_insert_hook = new RestAfterInsertCampaignActionHookDispatcher( $this->make_hook_logger() );
		$this->delete_post_hook = new DeletePostActionHookDispatcher( $this->make_hook_logger() );

		$campaign_factory = new CampaignFactory();
		$find_campaign = new FindCampaignByIdHandler( $this->campaign_repository );
		$delete_campaign = new DeleteCampaignHandler(
			$this->campaign_repository,
			$this->donation_repository,
			$this->event_bus,
		);
		$settings_reader = $this->make_settings_reader();

		$boot_unit = new SyncPostToCampaignBootUnit(
			$this->rest_pre_insert_hook,
			$this->rest_prepare_hook,
			$this->rest_after_insert_hook,
			$this->delete_post_hook,
			$this->enqueue_block_editor_assets_hook,
			$find_campaign,
			$delete_campaign,
			new RestPreInsertCampaignSyncDataExtractor( $settings_reader ),
			new RestPreInsertCampaignSyncDataValidator( $campaign_factory, $find_campaign ),
			new RestAfterInsertCampaignSyncDataExtractor( $settings_reader ),
			new RestAfterInsertCampaignSynchronizer(
				$this->make_campaign_command_service( $campaign_factory, $delete_campaign ),
				$find_campaign,
			),
			new BootUnitLogger( $this->psr_logger ),
		);

		$boot_unit->boot();
	}

	#[Test]
	public function reject_when_campaign_cannot_be_synced_returns_error_for_invalid_payload(): void {

		$prepared_post = new stdClass();

		$result = $this->rest_pre_insert_hook->dispatch(
			$prepared_post,
			new WpRestRequest( json_params: [ 'title' => 'Missing required fields' ] ),
		);

		self::assertInstanceOf( WP_Error::class, $result );
		self::assertSame( 'fundrik_campaign_invalid_payload', $result->get_error_code() );
	}

	#[Test]
	public function reject_when_campaign_cannot_be_synced_returns_error_for_stale_version(): void {

		$this->campaign_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->matches_campaign_id( 10 ) )
			->andReturn( $this->make_campaign( id: 10, version: 5, target_amount: null ) );

		$result = $this->rest_pre_insert_hook->dispatch(
			new stdClass(),
			$this->make_sync_request( id: 10, version: 3 ),
		);

		self::assertInstanceOf( WP_Error::class, $result );
		self::assertSame( 'fundrik_campaign_version_mismatch', $result->get_error_code() );
		self::assertSame( 409, $result->get_error_data()['status'] );
	}

	#[Test]
	public function reject_when_campaign_cannot_be_synced_keeps_valid_prepared_post(): void {

		$this->campaign_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->matches_campaign_id( 10 ) )
			->andReturn( $this->make_campaign( id: 10, version: 5, target_amount: null ) );

		$prepared_post = new stdClass();

		$result = $this->rest_pre_insert_hook->dispatch(
			$prepared_post,
			$this->make_sync_request( id: 10, version: 5 ),
		);

		self::assertSame( $prepared_post, $result );
	}

	#[Test]
	public function attach_campaign_version_for_sync_exposes_initial_version_for_missing_campaign(): void {

		$this->campaign_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->matches_campaign_id( 11 ) )
			->andReturn( null );

		$response = new WpRestResponse( 'not-an-array' );

		$result = $this->rest_prepare_hook->dispatch(
			$response,
			$this->make_post( 11 ),
			new WpRestRequest(),
		);

		self::assertSame( $response, $result );
		self::assertSame(
			[ 'meta' => [ CampaignPostTypeConfig::ENTITY_VERSION_FIELD_NAME => 1 ] ],
			$response->get_data(),
		);
	}

	#[Test]
	public function attach_campaign_version_for_sync_exposes_persisted_version_and_preserves_response_data(): void {

		$this->campaign_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->matches_campaign_id( 12 ) )
			->andReturn( $this->make_campaign( id: 12, version: 7 ) );

		$response = new WpRestResponse(
			[
				'id' => 12,
				'meta' => [ 'existing' => 'value' ],
			],
		);

		$this->rest_prepare_hook->dispatch(
			$response,
			$this->make_post( 12 ),
			new WpRestRequest(),
		);

		self::assertSame(
			[
				'id' => 12,
				'meta' => [
					'existing' => 'value',
					CampaignPostTypeConfig::ENTITY_VERSION_FIELD_NAME => 7,
				],
			],
			$response->get_data(),
		);
	}

	#[Test]
	public function attach_campaign_version_for_sync_logs_lookup_failure_and_keeps_response_unchanged(): void {

		$this->campaign_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->matches_campaign_id( 13 ) )
			->andThrow( new FakeCampaignRepositoryException( 'Database failed.' ) );

		$this->psr_logger
			->shouldReceive( 'error' )
			->once()
			->with(
				'Failed to resolve campaign version for REST response.',
				Mockery::on(
					static fn ( array $context ): bool => $context['service_class'] === SyncPostToCampaignBootUnit::class
						&& $context['post_id'] === 13
						&& $context['campaign_id'] === 13
						&& $context['exception'] instanceof FindCampaignByIdException,
				),
			);

		$response = new WpRestResponse( [ 'id' => 13 ] );

		$result = $this->rest_prepare_hook->dispatch(
			$response,
			$this->make_post( 13 ),
			new WpRestRequest(),
		);

		self::assertSame( $response, $result );
		self::assertSame( [ 'id' => 13 ], $response->get_data() );
	}

	#[Test]
	public function enqueue_block_editor_script_skips_non_campaign_screen(): void {

		Functions\expect( 'get_current_screen' )
			->once()
			->andReturn( new WpScreen( 'post' ) );
		Functions\expect( 'wp_enqueue_script' )->never();

		$this->enqueue_block_editor_assets_hook->dispatch();
	}

	#[Test]
	public function enqueue_block_editor_script_enqueues_sync_script_for_campaign_screen(): void {

		Functions\expect( 'get_current_screen' )
			->once()
			->andReturn( new WpScreen( CampaignPostTypeConfig::ID ) );

		Functions\expect( 'wp_enqueue_script' )
			->once()
			->with(
				'fundrik-editor-save-sync',
				PluginUrl::JavaScripts->file( 'fundrik-editor-save-sync.js' ),
				[ 'wp-data', 'wp-core-data', 'wp-editor', 'wp-api-fetch' ],
				FUNDRIK_VERSION,
				[ 'in_footer' => true ],
			);

		$this->enqueue_block_editor_assets_hook->dispatch();
	}

	#[Test]
	public function sync_campaign_after_rest_save_logs_invalid_payload(): void {

		$this->expect_default_campaign_meta( 21 );

		$this->psr_logger
			->shouldReceive( 'error' )
			->once()
			->with(
				'Campaign synchronization after REST save failed: payload is not usable.',
				[
					'service_class' => SyncPostToCampaignBootUnit::class,
					'logger_class' => BootUnitLogger::class,
					'component' => 'boot_units',
					'layer' => 'integration',
					'system' => 'wordpress',
					'post_id' => 21,
				],
			);

		Functions\expect( 'fundrik_set_failure_message' )
			->once()
			->with( Mockery::type( 'string' ) );

		$this->rest_after_insert_hook->dispatch(
			$this->make_post( 21 ),
			new WpRestRequest(),
			true,
		);
	}

	#[Test]
	public function sync_campaign_after_rest_save_logs_synchronization_failure(): void {

		$this->expect_default_campaign_meta( 22 );

		$this->campaign_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->matches_campaign_id( 22 ) )
			->andThrow( new FakeCampaignRepositoryException( 'Database failed.' ) );

		$this->psr_logger
			->shouldReceive( 'error' )
			->once()
			->with(
				'Campaign synchronization after REST save failed.',
				Mockery::on(
					static fn ( array $context ): bool => $context['service_class'] === SyncPostToCampaignBootUnit::class
						&& $context['post_id'] === 22
						&& $context['campaign_id'] === 22
						&& $context['version'] === 3
						&& $context['exception'] instanceof FindCampaignByIdException,
				),
			);

		Functions\expect( 'fundrik_set_failure_message' )
			->once()
			->with( 'Failed to retrieve campaign "22".' );

		$this->rest_after_insert_hook->dispatch(
			$this->make_post( 22 ),
			$this->make_after_insert_request( 3 ),
			false,
		);
	}

	#[Test]
	public function sync_campaign_after_rest_save_creates_campaign_from_saved_post(): void {

		$this->expect_default_campaign_meta( 23 );

		$this->campaign_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->matches_campaign_id( 23 ) )
			->andReturn( null );

		$this->campaign_repository
			->shouldReceive( 'insert' )
			->once()
			->with(
				Mockery::on(
					static fn ( Campaign $campaign ): bool => $campaign->get_id()->get_value() === 23
						&& $campaign->get_version()->get_value() === 1
						&& $campaign->get_title() === 'Saved campaign'
						&& $campaign->accepts_donations()
						&& ! $campaign->has_target()
						&& $campaign->get_target()->get_currency()->get_code() === 'RUB',
				),
			)
			->andReturnUsing( static fn ( Campaign $campaign ): Campaign => $campaign );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once();

		Functions\expect( 'delete_post_meta' )
			->once()
			->with( 23, CampaignPostTypeConfig::META_TARGET_AMOUNT );

		$this->rest_after_insert_hook->dispatch(
			$this->make_post( 23, title: 'Saved campaign' ),
			$this->make_after_insert_request( 1 ),
			true,
		);
	}

	#[Test]
	public function delete_campaign_after_post_delete_skips_non_campaign_post(): void {

		$this->donation_repository->shouldNotReceive( 'exists_by_campaign_id' );

		$this->delete_post_hook->dispatch( 31, $this->make_post( 31, post_type: 'post' ) );
	}

	#[Test]
	public function delete_campaign_after_post_delete_deletes_campaign(): void {

		$this->donation_repository
			->shouldReceive( 'exists_by_campaign_id' )
			->once()
			->with( $this->matches_campaign_id( 32 ) )
			->andReturn( false );

		$this->campaign_repository
			->shouldReceive( 'delete' )
			->once()
			->with( $this->matches_campaign_id( 32 ) );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once();

		$this->delete_post_hook->dispatch( 32, $this->make_post( 32 ) );
	}

	#[Test]
	public function delete_campaign_after_post_delete_logs_deletion_failure(): void {

		$this->donation_repository
			->shouldReceive( 'exists_by_campaign_id' )
			->once()
			->with( $this->matches_campaign_id( 33 ) )
			->andReturn( false );

		$this->campaign_repository
			->shouldReceive( 'delete' )
			->once()
			->with( $this->matches_campaign_id( 33 ) )
			->andThrow( new FakeCampaignRepositoryException( 'Database failed.' ) );

		$this->psr_logger
			->shouldReceive( 'error' )
			->once()
			->with(
				'Campaign synchronization after post delete failed.',
				Mockery::on(
					static fn ( array $context ): bool => $context['service_class'] === SyncPostToCampaignBootUnit::class
						&& $context['post_id'] === 33
						&& $context['campaign_id'] === 33
						&& $context['post_type'] === CampaignPostTypeConfig::ID
						&& $context['exception'] instanceof DeleteCampaignException,
				),
			);

		Functions\expect( 'fundrik_set_failure_message' )
			->once()
			->with( 'Failed to delete campaign "33".' );

		$this->delete_post_hook->dispatch( 33, $this->make_post( 33 ) );
	}

	private function make_campaign_command_service(
		CampaignFactory $campaign_factory,
		DeleteCampaignHandler $delete_campaign,
	): CampaignCommandService {

		return new CampaignCommandService(
			new CreateCampaignHandler( $this->campaign_repository, $this->event_bus ),
			$campaign_factory,
			new SyncCampaignFromSnapshotHandler( $this->campaign_repository, $this->event_bus ),
			new RenameCampaignHandler( $this->campaign_repository, $this->event_bus ),
			new EnableCampaignDonationsHandler( $this->campaign_repository, $this->event_bus ),
			new DisableCampaignDonationsHandler( $this->campaign_repository, $this->event_bus ),
			new ChangeCampaignTargetHandler( $this->campaign_repository, $this->event_bus ),
			$delete_campaign,
		);
	}

	private function make_hook_logger(): HookDispatcherLogger {

		return new HookDispatcherLogger( $this->psr_logger );
	}

	private function make_settings_reader(): AdminSettingsReader {

		return new AdminSettingsReader(
			new OptionReader(
				new FakeStorage(
					[
						'fundrik_general_currency_setting' => 'RUB',
						'fundrik_campaign_default_accepts_donations_setting' => true,
						'fundrik_campaign_default_has_target_setting' => false,
					],
				),
			),
			new GeneralSettingsGroup( new CurrencySetting() ),
			new CampaignSettingsGroup(
				new CampaignDefaultAcceptsDonationsSetting(),
				new CampaignDefaultHasTargetSetting(),
			),
		);
	}

	private function make_sync_request( int $id, int $version ): WpRestRequest {

		return new WpRestRequest(
			json_params: [
				'id' => $id,
				'title' => 'Campaign title',
				'meta' => [
					CampaignPostTypeConfig::ENTITY_VERSION_FIELD_NAME => $version,
					CampaignPostTypeConfig::META_ACCEPTS_DONATIONS => true,
					CampaignPostTypeConfig::META_HAS_TARGET => false,
				],
			],
		);
	}

	private function make_after_insert_request( int $version ): WpRestRequest {

		return new WpRestRequest(
			json_params: [
				'meta' => [ CampaignPostTypeConfig::ENTITY_VERSION_FIELD_NAME => $version ],
			],
		);
	}

	private function make_post(
		int $id,
		string $title = 'Campaign title',
		string $post_type = CampaignPostTypeConfig::ID,
	): WpPost {

		return new WpPost( post_type: $post_type, id: $id, title: $title );
	}

	private function expect_default_campaign_meta( int $post_id ): void {

		foreach (
			[
				CampaignPostTypeConfig::META_ACCEPTS_DONATIONS,
				CampaignPostTypeConfig::META_HAS_TARGET,
				CampaignPostTypeConfig::META_TARGET_CURRENCY,
			] as $meta_key
		) {
			Functions\expect( 'metadata_exists' )
				->once()
				->with( 'post', $post_id, $meta_key )
				->andReturn( false );
		}
	}

	private function matches_campaign_id( int $value ): ClosureMatcher {

		return Mockery::on(
			static fn ( EntityId $id ): bool => $id->get_value() === $value,
		);
	}
}
