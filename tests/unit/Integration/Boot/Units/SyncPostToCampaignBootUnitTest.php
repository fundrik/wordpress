<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Boot\Units;

use Brain\Monkey\Functions;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositorySaveOutcome;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositorySaveResult;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Campaigns\Domain\CampaignFactory;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\WordPress\Infrastructure\Helpers\PluginUrl;
use Fundrik\WordPress\Integration\Boot\BootUnitLogger;
use Fundrik\WordPress\Integration\Boot\Units\SyncPostToCampaignBootUnit;
use Fundrik\WordPress\Integration\Helpers\Meta;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\DeletePostActionHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\EnqueueBlockEditorAssetsActionHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\RestAfterInsertCampaignActionHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\RestPreInsertCampaignFilterHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\RestPrepareCampaignFilterHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherLogger;
use Fundrik\WordPress\Integration\PostTypes\Configs\CampaignPostTypeConfig;
use Fundrik\WordPress\Integration\SyncPostToCampaign\RestAfterInsertCampaignSyncDataExtractor;
use Fundrik\WordPress\Integration\SyncPostToCampaign\RestAfterInsertCampaignSynchronizer;
use Fundrik\WordPress\Integration\SyncPostToCampaign\RestCampaignSyncDataDto;
use Fundrik\WordPress\Integration\SyncPostToCampaign\RestPreInsertCampaignSyncDataExtractor;
use Fundrik\WordPress\Integration\SyncPostToCampaign\RestPreInsertCampaignSyncDataValidator;
use Fundrik\WordPress\Tests\Fixtures\FakeCampaignRepositoryException;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use Psr\Log\LoggerInterface;
use stdClass;
use WP_Error;
use WP_Post;
use WP_REST_Request;
use WP_REST_Response;
use WP_Screen;

#[CoversClass( SyncPostToCampaignBootUnit::class )]
#[UsesClass( BootUnitLogger::class )]
#[UsesClass( HookDispatcherLogger::class )]
#[UsesClass( RestPreInsertCampaignFilterHookDispatcher::class )]
#[UsesClass( RestPrepareCampaignFilterHookDispatcher::class )]
#[UsesClass( RestAfterInsertCampaignActionHookDispatcher::class )]
#[UsesClass( DeletePostActionHookDispatcher::class )]
#[UsesClass( EnqueueBlockEditorAssetsActionHookDispatcher::class )]
#[UsesClass( CampaignPostTypeConfig::class )]
#[UsesClass( RestPreInsertCampaignSyncDataExtractor::class )]
#[UsesClass( RestPreInsertCampaignSyncDataValidator::class )]
#[UsesClass( RestAfterInsertCampaignSyncDataExtractor::class )]
#[UsesClass( RestAfterInsertCampaignSynchronizer::class )]
#[UsesClass( RestCampaignSyncDataDto::class )]
#[UsesClass( Meta::class )]
#[UsesClass( PluginUrl::class )]
final class SyncPostToCampaignBootUnitTest extends WordPressTestCase {

	private RestPreInsertCampaignFilterHookDispatcher $rest_pre_insert_hook;
	private RestPrepareCampaignFilterHookDispatcher $rest_prepare_hook;
	private RestAfterInsertCampaignActionHookDispatcher $rest_after_insert_hook;
	private DeletePostActionHookDispatcher $delete_post_hook;
	private EnqueueBlockEditorAssetsActionHookDispatcher $enqueue_block_editor_assets_hook;

	private CampaignRepositoryPort&MockInterface $campaign_repository;
	private CampaignRepositoryPort&MockInterface $validator_campaign_repository;
	private CampaignRepositoryPort&MockInterface $synchronizer_campaign_repository;

	private RestPreInsertCampaignSyncDataExtractor $pre_insert_extractor;
	private RestPreInsertCampaignSyncDataValidator $pre_insert_validator;
	private RestAfterInsertCampaignSyncDataExtractor $after_insert_extractor;
	private RestAfterInsertCampaignSynchronizer $after_insert_synchronizer;

	private LoggerInterface&MockInterface $psr_logger;
	private BootUnitLogger $logger;
	private CampaignFactory $campaign_factory;

	private SyncPostToCampaignBootUnit $boot_unit;

	protected function setUp(): void {

		parent::setUp();

		$this->psr_logger = Mockery::mock( LoggerInterface::class );

		$this->rest_pre_insert_hook = new RestPreInsertCampaignFilterHookDispatcher(
			new HookDispatcherLogger( $this->psr_logger ),
		);

		$this->rest_prepare_hook = new RestPrepareCampaignFilterHookDispatcher(
			new HookDispatcherLogger( $this->psr_logger ),
		);

		$this->rest_after_insert_hook = new RestAfterInsertCampaignActionHookDispatcher(
			new HookDispatcherLogger( $this->psr_logger ),
		);

		$this->delete_post_hook = new DeletePostActionHookDispatcher(
			new HookDispatcherLogger( $this->psr_logger ),
		);

		$this->enqueue_block_editor_assets_hook = new EnqueueBlockEditorAssetsActionHookDispatcher(
			new HookDispatcherLogger( $this->psr_logger ),
		);

		$this->campaign_factory = new CampaignFactory();

		$this->campaign_repository = Mockery::mock( CampaignRepositoryPort::class );
		$this->validator_campaign_repository = Mockery::mock( CampaignRepositoryPort::class );
		$this->synchronizer_campaign_repository = Mockery::mock( CampaignRepositoryPort::class );

		$this->pre_insert_extractor = new RestPreInsertCampaignSyncDataExtractor();
		$this->pre_insert_validator = new RestPreInsertCampaignSyncDataValidator(
			$this->campaign_factory,
			$this->validator_campaign_repository,
		);
		$this->after_insert_extractor = new RestAfterInsertCampaignSyncDataExtractor();
		$this->after_insert_synchronizer = new RestAfterInsertCampaignSynchronizer(
			$this->campaign_factory,
			$this->synchronizer_campaign_repository,
		);

		$this->logger = new BootUnitLogger( $this->psr_logger );

		$this->boot_unit = new SyncPostToCampaignBootUnit(
			$this->rest_pre_insert_hook,
			$this->rest_prepare_hook,
			$this->rest_after_insert_hook,
			$this->delete_post_hook,
			$this->enqueue_block_editor_assets_hook,
			$this->campaign_repository,
			$this->pre_insert_extractor,
			$this->pre_insert_validator,
			$this->after_insert_extractor,
			$this->after_insert_synchronizer,
			$this->logger,
		);
	}

	#[Test]
	public function boot_rejects_pre_insert_when_extractor_returns_wp_error(): void {

		$this->validator_campaign_repository->shouldNotReceive( 'find_by_id' );

		$this->boot_unit->boot();

		$prepared_post = new stdClass();
		$request = $this->make_request(
			[
				// Missing "id" and "meta".
				'title' => 'Payload without required fields',
			],
		);

		$returned = $this->rest_pre_insert_hook->handle( $prepared_post, $request );

		self::assertInstanceOf( WP_Error::class, $returned );
		self::assertSame( 'fundrik_campaign_invalid_payload', $returned->get_error_code() );
	}

	#[Test]
	public function boot_rejects_pre_insert_when_validator_returns_version_mismatch_error(): void {

		$this->validator_campaign_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->with( Mockery::type( EntityId::class ) )
			->andReturn( $this->create_persisted_campaign( 10, 5 ) );

		$this->boot_unit->boot();

		$prepared_post = new stdClass();
		$request = $this->make_request(
			[
				'id' => 10,
				'title' => 'Updated title',
				'meta' => [
					CampaignPostTypeConfig::ENTITY_VERSION_FIELD_NAME => 3,
					CampaignPostTypeConfig::META_IS_OPEN => true,
					CampaignPostTypeConfig::META_HAS_TARGET => false,
					CampaignPostTypeConfig::META_TARGET_AMOUNT => 0,
				],
			],
		);

		$returned = $this->rest_pre_insert_hook->handle( $prepared_post, $request );

		self::assertInstanceOf( WP_Error::class, $returned );
		self::assertSame( 'fundrik_campaign_version_mismatch', $returned->get_error_code() );
		self::assertSame( 409, $returned->get_error_data()['status'] );
	}

	#[Test]
	public function boot_allows_pre_insert_when_payload_is_valid_and_versions_match(): void {

		$this->validator_campaign_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->with( Mockery::type( EntityId::class ) )
			->andReturn( $this->create_persisted_campaign( 10, 5 ) );

		$this->boot_unit->boot();

		$prepared_post = new stdClass();
		$request = $this->make_request(
			[
				'id' => 10,
				'title' => 'Updated title',
				'meta' => [
					CampaignPostTypeConfig::ENTITY_VERSION_FIELD_NAME => 5,
					CampaignPostTypeConfig::META_IS_OPEN => true,
					CampaignPostTypeConfig::META_HAS_TARGET => false,
					CampaignPostTypeConfig::META_TARGET_AMOUNT => 0,
				],
			],
		);

		$returned = $this->rest_pre_insert_hook->handle( $prepared_post, $request );

		self::assertSame( $prepared_post, $returned );
	}

	#[Test]
	public function boot_attaches_version_to_rest_response_with_initial_value_when_campaign_is_missing(): void {

		$this->campaign_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->with( Mockery::type( EntityId::class ) )
			->andReturn( null );

		$response = Mockery::mock( WP_REST_Response::class );
		$response
			->shouldReceive( 'get_data' )
			->once()
			->andReturn( 'not-an-array' );

		$response
			->shouldReceive( 'set_data' )
			->once()
			->with(
				[
					'meta' => [
						CampaignPostTypeConfig::ENTITY_VERSION_FIELD_NAME => 1,
					],
				],
			);

		$post = $this->make_post( 10 );
		$request = Mockery::mock( WP_REST_Request::class );

		$this->boot_unit->boot();

		$returned = $this->rest_prepare_hook->handle( $response, $post, $request );

		self::assertSame( $response, $returned );
	}

	#[Test]
	public function boot_attaches_persisted_version_to_rest_response_meta(): void {

		$this->campaign_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->with( Mockery::type( EntityId::class ) )
			->andReturn( $this->create_persisted_campaign( 15, 7 ) );

		$response = Mockery::mock( WP_REST_Response::class );
		$response
			->shouldReceive( 'get_data' )
			->once()
			->andReturn(
				[
					'id' => 15,
					'meta' => [
						'foo' => 'bar',
					],
				],
			);

		$response
			->shouldReceive( 'set_data' )
			->once()
			->with(
				Mockery::on(
					static function ( array $data ): bool {

						if ( ( $data['id'] ?? null ) !== 15 ) {
							return false;
						}

						if ( ( $data['meta']['foo'] ?? null ) !== 'bar' ) {
							return false;
						}

						return ( $data['meta'][ CampaignPostTypeConfig::ENTITY_VERSION_FIELD_NAME ] ?? null ) === 7;
					},
				),
			);

		$post = $this->make_post( 15 );
		$request = Mockery::mock( WP_REST_Request::class );

		$this->boot_unit->boot();

		$returned = $this->rest_prepare_hook->handle( $response, $post, $request );

		self::assertSame( $response, $returned );
	}

	#[Test]
	public function boot_normalizes_non_array_meta_before_attaching_version_to_rest_response(): void {

		$this->campaign_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->with( Mockery::type( EntityId::class ) )
			->andReturn( $this->create_persisted_campaign( 16, 8 ) );

		$response = Mockery::mock( WP_REST_Response::class );
		$response
			->shouldReceive( 'get_data' )
			->once()
			->andReturn(
				[
					'id' => 16,
					'meta' => 'not-an-array',
					'status' => 'publish',
				],
			);

		$response
			->shouldReceive( 'set_data' )
			->once()
			->with(
				[
					'id' => 16,
					'meta' => [
						CampaignPostTypeConfig::ENTITY_VERSION_FIELD_NAME => 8,
					],
					'status' => 'publish',
				],
			);

		$post = $this->make_post( 16 );
		$request = Mockery::mock( WP_REST_Request::class );

		$this->boot_unit->boot();

		$returned = $this->rest_prepare_hook->handle( $response, $post, $request );

		self::assertSame( $response, $returned );
	}

	#[Test]
	public function boot_logs_error_and_keeps_response_unchanged_when_version_lookup_fails(): void {

		$exception = new FakeCampaignRepositoryException( 'DB failed.' );

		$this->campaign_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->with( Mockery::type( EntityId::class ) )
			->andThrow( $exception );

		$this->psr_logger
			->shouldReceive( 'error' )
			->once()
			->with(
				'Failed to resolve campaign version for REST response.',
				Mockery::on(
					static function ( array $context ) use ( $exception ): bool {

						if ( ( $context['service_class'] ?? null ) !== SyncPostToCampaignBootUnit::class ) {
							return false;
						}

						if ( ( $context['component'] ?? null ) !== 'boot_units' ) {
							return false;
						}

						if ( ( $context['post_id'] ?? null ) !== 44 ) {
							return false;
						}

						if ( ( $context['entity_id'] ?? null ) !== 44 ) {
							return false;
						}

						return ( $context['exception'] ?? null ) === $exception;
					},
				),
			);

		$response = Mockery::mock( WP_REST_Response::class );
		$response->shouldNotReceive( 'get_data' );
		$response->shouldNotReceive( 'set_data' );

		$post = $this->make_post( 44 );
		$request = Mockery::mock( WP_REST_Request::class );

		$this->boot_unit->boot();

		$returned = $this->rest_prepare_hook->handle( $response, $post, $request );

		self::assertSame( $response, $returned );
	}

	#[Test]
	public function boot_does_not_enqueue_editor_script_when_screen_is_missing(): void {

		Functions\expect( 'get_current_screen' )
			->once()
			->andReturn( null );

		Functions\expect( 'wp_enqueue_script' )->never();

		$this->boot_unit->boot();

		$this->enqueue_block_editor_assets_hook->handle();
	}

	#[Test]
	public function boot_does_not_enqueue_editor_script_for_non_campaign_post_type(): void {

		$screen = Mockery::mock( WP_Screen::class );
		$screen->post_type = 'post';

		Functions\expect( 'get_current_screen' )
			->once()
			->andReturn( $screen );

		Functions\expect( 'wp_enqueue_script' )->never();

		$this->boot_unit->boot();

		$this->enqueue_block_editor_assets_hook->handle();
	}

	#[Test]
	public function boot_enqueues_editor_script_for_campaign_post_type(): void {

		$screen = Mockery::mock( WP_Screen::class );
		$screen->post_type = CampaignPostTypeConfig::ID;

		Functions\expect( 'get_current_screen' )
			->once()
			->andReturn( $screen );

		Functions\expect( 'wp_enqueue_script' )
			->once()
			->with(
				'fundrik-editor-save-sync',
				PluginUrl::JavaScripts->file( 'fundrik-editor-save-sync.js' ),
				[
					'wp-data',
					'wp-core-data',
					'wp-editor',
					'wp-api-fetch',
				],
				FUNDRIK_VERSION,
				[ 'in_footer' => true ],
			);

		$this->boot_unit->boot();

		$this->enqueue_block_editor_assets_hook->handle();
	}

	#[Test]
	public function boot_deletes_campaign_after_campaign_post_delete(): void {

		$post = $this->make_post( 31, 'Campaign title', CampaignPostTypeConfig::ID );

		$this->campaign_repository
			->shouldReceive( 'delete' )
			->once()
			->with(
				Mockery::on(
					static fn ( EntityId $id ): bool => $id->get_value() === 31,
				),
			);

		$this->psr_logger
			->shouldReceive( 'info' )
			->once()
			->with(
				'Campaign synchronization after post delete completed.',
				Mockery::subset(
					[
						'service_class' => SyncPostToCampaignBootUnit::class,
						'component' => 'boot_units',
						'post_id' => 31,
						'entity_id' => 31,
						'post_type' => CampaignPostTypeConfig::ID,
					],
				),
			);

		Functions\expect( 'fundrik_set_failure_message' )->never();

		$this->boot_unit->boot();

		$this->delete_post_hook->handle( 31, $post );
	}

	#[Test]
	public function boot_skips_campaign_delete_for_non_campaign_post_type(): void {

		$post = $this->make_post( 32, 'Regular post', 'post' );

		$this->campaign_repository->shouldNotReceive( 'delete' );
		$this->psr_logger->shouldNotReceive( 'info' );

		Functions\expect( 'fundrik_set_failure_message' )->never();

		$this->boot_unit->boot();

		$this->delete_post_hook->handle( 32, $post );
	}

	#[Test]
	public function boot_logs_error_and_sets_failure_message_when_campaign_delete_fails(): void {

		$exception = new FakeCampaignRepositoryException( 'DB delete failed.' );
		$post = $this->make_post( 33, 'Campaign title', CampaignPostTypeConfig::ID );

		$this->campaign_repository
			->shouldReceive( 'delete' )
			->once()
			->with(
				Mockery::on(
					static fn ( EntityId $id ): bool => $id->get_value() === 33,
				),
			)
			->andThrow( $exception );

		$this->psr_logger
			->shouldReceive( 'error' )
			->once()
			->with(
				'Campaign synchronization after post delete failed.',
				Mockery::on(
					static function ( array $context ) use ( $exception ): bool {

						if ( ( $context['service_class'] ?? null ) !== SyncPostToCampaignBootUnit::class ) {
							return false;
						}

						if ( ( $context['component'] ?? null ) !== 'boot_units' ) {
							return false;
						}

						if ( ( $context['post_id'] ?? null ) !== 33 ) {
							return false;
						}

						if ( ( $context['entity_id'] ?? null ) !== 33 ) {
							return false;
						}

						if ( ( $context['post_type'] ?? null ) !== CampaignPostTypeConfig::ID ) {
							return false;
						}

						return ( $context['exception'] ?? null ) === $exception;
					},
				),
			);

		Functions\expect( 'fundrik_set_failure_message' )
			->once()
			->with( Mockery::type( 'string' ) );

		$this->boot_unit->boot();

		$this->delete_post_hook->handle( 33, $post );
	}

	#[Test]
	public function boot_logs_error_and_sets_failure_message_when_after_insert_payload_is_invalid(): void {

		$post = $this->make_post( 21, 'Campaign title' );
		$request = $this->make_request( [] );

		$this->expect_after_insert_meta_defaults( 21 );
		$this->synchronizer_campaign_repository->shouldNotReceive( 'find_by_id' );
		$this->synchronizer_campaign_repository->shouldNotReceive( 'save' );

		$this->psr_logger
			->shouldReceive( 'error' )
			->once()
			->with(
				'Campaign synchronization after REST save failed: payload is not usable.',
				Mockery::subset(
					[
						'service_class' => SyncPostToCampaignBootUnit::class,
						'component' => 'boot_units',
						'post_id' => 21,
						'creating' => true,
					],
				),
			);

		Functions\expect( 'fundrik_set_failure_message' )
			->once()
			->with( Mockery::type( 'string' ) );

		$this->boot_unit->boot();

		// Exception is caught by RestAfterInsertCampaignActionHookDispatcher.
		$this->rest_after_insert_hook->handle( $post, $request, true );
	}

	#[Test]
	public function boot_logs_error_and_sets_failure_message_when_synchronizer_throws(): void {

		$post = $this->make_post( 22, '' ); // Empty title triggers CampaignFactoryException.
		$request = $this->make_request(
			[
				'meta' => [
					CampaignPostTypeConfig::ENTITY_VERSION_FIELD_NAME => 3,
				],
			],
		);

		$this->expect_after_insert_meta_defaults( 22 );

		$this->synchronizer_campaign_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->with( Mockery::type( EntityId::class ) )
			->andReturn( null );

		$this->synchronizer_campaign_repository->shouldNotReceive( 'save' );

		$this->psr_logger
			->shouldReceive( 'error' )
			->once()
			->with(
				'Campaign synchronization after REST save failed.',
				Mockery::on(
					static function ( array $context ): bool {

						if ( ( $context['service_class'] ?? null ) !== SyncPostToCampaignBootUnit::class ) {
							return false;
						}

						if ( ( $context['component'] ?? null ) !== 'boot_units' ) {
							return false;
						}

						if ( ( $context['post_id'] ?? null ) !== 22 ) {
							return false;
						}

						if ( ( $context['entity_id'] ?? null ) !== 22 ) {
							return false;
						}

						if ( ( $context['version'] ?? null ) !== 3 ) {
							return false;
						}

						if ( ( $context['creating'] ?? null ) !== false ) {
							return false;
						}

						return ( $context['exception'] ?? null ) instanceof \Fundrik\Core\Components\Campaigns\Domain\Exceptions\CampaignFactoryException;
					},
				),
			);

		Functions\expect( 'fundrik_set_failure_message' )
			->once()
			->with( Mockery::type( 'string' ) );

		$this->boot_unit->boot();

		// Exception is caught by RestAfterInsertCampaignActionHookDispatcher.
		$this->rest_after_insert_hook->handle( $post, $request, false );
	}

	#[Test]
	public function boot_logs_info_when_after_insert_synchronization_completes(): void {

		$post = $this->make_post( 23, 'Campaign title' );
		$request = $this->make_request(
			[
				'meta' => [
					CampaignPostTypeConfig::ENTITY_VERSION_FIELD_NAME => 4,
				],
			],
		);

		$this->expect_after_insert_meta_defaults( 23 );

		$this->synchronizer_campaign_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->with( Mockery::type( EntityId::class ) )
			->andReturn( null );

		$this->synchronizer_campaign_repository
			->shouldReceive( 'save' )
			->once()
			->with( Mockery::type( Campaign::class ) )
			->andReturnUsing(
				static fn ( Campaign $campaign ): CampaignRepositorySaveOutcome => new CampaignRepositorySaveOutcome(
					result: CampaignRepositorySaveResult::Inserted,
					campaign: $campaign,
				),
			);

		$this->psr_logger
			->shouldReceive( 'info' )
			->once()
			->with(
				'Campaign synchronization after REST save completed.',
				Mockery::subset(
					[
						'service_class' => SyncPostToCampaignBootUnit::class,
						'component' => 'boot_units',
						'post_id' => 23,
						'entity_id' => 23,
						'version' => 4,
						'creating' => true,
					],
				),
			);

		Functions\expect( 'fundrik_set_failure_message' )->never();

		$this->boot_unit->boot();

		$this->rest_after_insert_hook->handle( $post, $request, true );
	}

	#[Test]
	public function boot_saves_inactive_campaign_when_post_status_is_not_publish(): void {

		$post = $this->make_post( 24, 'Campaign title', CampaignPostTypeConfig::ID, 'draft' );
		$request = $this->make_request(
			[
				'meta' => [
					CampaignPostTypeConfig::ENTITY_VERSION_FIELD_NAME => 4,
				],
			],
		);

		$this->expect_after_insert_meta_defaults( 24 );

		$this->synchronizer_campaign_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->with( Mockery::type( EntityId::class ) )
			->andReturn( null );

		$this->synchronizer_campaign_repository
			->shouldReceive( 'save' )
			->once()
			->with( Mockery::type( Campaign::class ) )
			->andReturnUsing(
				static function ( Campaign $campaign ): CampaignRepositorySaveOutcome {

					self::assertFalse( $campaign->is_active() );

					return new CampaignRepositorySaveOutcome(
						result: CampaignRepositorySaveResult::Inserted,
						campaign: $campaign,
					);
				},
			);

		$this->psr_logger
			->shouldReceive( 'info' )
			->once()
			->with(
				'Campaign synchronization after REST save completed.',
				Mockery::subset(
					[
						'service_class' => SyncPostToCampaignBootUnit::class,
						'component' => 'boot_units',
						'post_id' => 24,
						'entity_id' => 24,
						'version' => 4,
						'creating' => true,
					],
				),
			);

		Functions\expect( 'fundrik_set_failure_message' )->never();

		$this->boot_unit->boot();

		$this->rest_after_insert_hook->handle( $post, $request, true );
	}

	private function create_persisted_campaign( int $id, int $version ): Campaign {

		return $this->campaign_factory->create(
			id: $id,
			version: $version,
			title: 'Persisted campaign',
			is_active: true,
			is_open: true,
			has_target: false,
			target_amount: 0,
		);
	}

	private function make_request( array $json_payload ): WP_REST_Request {

		$request = Mockery::mock( WP_REST_Request::class );

		$request
			->shouldReceive( 'get_json_params' )
			->once()
			->andReturn( $json_payload );

		return $request;
	}

	private function make_post(
		int $id,
		string $title = 'Campaign title',
		string $post_type = CampaignPostTypeConfig::ID,
		string $status = 'publish',
	): WP_Post {

		$post = Mockery::mock( WP_Post::class );
		$post->ID = $id;
		$post->post_title = $title;
		$post->post_type = $post_type;
		$post->post_status = $status;

		return $post;
	}

	private function expect_after_insert_meta_defaults( int $post_id ): void {

		Functions\expect( 'metadata_exists' )
			->once()
			->with( 'post', $post_id, CampaignPostTypeConfig::META_IS_OPEN )
			->andReturn( false );

		Functions\expect( 'metadata_exists' )
			->once()
			->with( 'post', $post_id, CampaignPostTypeConfig::META_HAS_TARGET )
			->andReturn( false );

		Functions\expect( 'metadata_exists' )
			->once()
			->with( 'post', $post_id, CampaignPostTypeConfig::META_TARGET_AMOUNT )
			->andReturn( false );

		Functions\expect( 'get_post_meta' )->never();
	}
}
