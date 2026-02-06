<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\Integration\HookToEventBridges\Bridges;

use Fundrik\WordPress\Infrastructure\EventDispatcher\InfrastructureEventDispatcherInterface;
use Fundrik\WordPress\Infrastructure\Integration\Events\FilterCampaignBeforeSavedViaRestEvent;
use Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\BridgeLogger;
use Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\Bridges\RestPreInsertCampaignFilterBridge;
use Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\InvalidBridgeArgumentException;
use Fundrik\WordPress\Infrastructure\Integration\PostTypes\Attributes\PostTypeId;
use Fundrik\WordPress\Infrastructure\Integration\PostTypes\Attributes\PostTypeIdReader;
use Fundrik\WordPress\Infrastructure\Integration\PostTypes\CampaignPostType;
use Fundrik\WordPress\Infrastructure\Integration\WordPressContext\WordPressContextInterface;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use Psr\Log\LoggerInterface;
use RuntimeException;
use stdClass;
use WP_Error;
use WP_REST_Request;

#[CoversClass( RestPreInsertCampaignFilterBridge::class )]
#[UsesClass( FilterCampaignBeforeSavedViaRestEvent::class )]
#[UsesClass( BridgeLogger::class )]
#[UsesClass( InvalidBridgeArgumentException::class )]
#[UsesClass( PostTypeId::class )]
#[UsesClass( PostTypeIdReader::class )]
final class RestPreInsertCampaignFilterBridgeTest extends WordPressTestCase {

	private WordPressContextInterface&MockInterface $context;
	private InfrastructureEventDispatcherInterface&MockInterface $dispatcher;

	private LoggerInterface&MockInterface $psr_logger;
	private BridgeLogger $logger;

	private PostTypeIdReader $post_type_id_reader;

	private RestPreInsertCampaignFilterBridge $bridge;

	protected function setUp(): void {

		parent::setUp();

		$this->context = Mockery::mock( WordPressContextInterface::class );
		$this->dispatcher = Mockery::mock( InfrastructureEventDispatcherInterface::class );

		$this->psr_logger = Mockery::mock( LoggerInterface::class )->shouldIgnoreMissing();
		$this->logger = new BridgeLogger( $this->psr_logger );

		$this->post_type_id_reader = new PostTypeIdReader();

		$this->bridge = new RestPreInsertCampaignFilterBridge(
			$this->context,
			$this->dispatcher,
			$this->post_type_id_reader,
			$this->logger,
		);
	}

	#[Test]
	public function register_resolves_post_type_registers_filter(): void {

		$this->bridge->register();

		$post_type = $this->post_type_id_reader->get_id( CampaignPostType::class );
		$hook_name = 'rest_pre_insert_' . $post_type;

		self::assertSame( 10, has_filter( $hook_name, $this->bridge->handle( ... ) ) );
	}

	#[Test]
	public function handle_dispatches_event_and_returns_original_when_unchanged(): void {

		$this->bridge->register();

		$prepared_post = new stdClass();
		$request = Mockery::mock( WP_REST_Request::class );

		$this->dispatcher
			->shouldReceive( 'dispatch' )
			->once()
			->with(
				Mockery::on(
					function ( object $event ) use ( $prepared_post, $request ): bool {

						if ( ! $event instanceof FilterCampaignBeforeSavedViaRestEvent ) {
							return false;
						}

						return $event->prepared_post === $prepared_post
							&& $event->request === $request
							&& $event->context === $this->context;
					},
				),
			);

		$returned = $this->bridge->handle( $prepared_post, $request );

		self::assertSame( $prepared_post, $returned );
	}

	#[Test]
	public function handle_dispatches_event_and_returns_modified_value_from_event(): void {

		$this->bridge->register();

		$prepared_post = new stdClass();
		$request = Mockery::mock( WP_REST_Request::class );

		$changed_post = new stdClass();
		$changed_post->changed = true;

		$this->dispatcher
			->shouldReceive( 'dispatch' )
			->once()
			->with(
				Mockery::on(
					function ( object $event ) use ( $prepared_post, $request, $changed_post ): bool {

						if ( ! $event instanceof FilterCampaignBeforeSavedViaRestEvent ) {
							return false;
						}

						if ( $event->prepared_post !== $prepared_post ) {
							return false;
						}

						if ( $event->request !== $request || $event->context !== $this->context ) {
							return false;
						}

						// Simulate listeners changing the prepared post.
						$event->prepared_post = $changed_post;

						return true;
					},
				),
			);

		$returned = $this->bridge->handle( $prepared_post, $request );

		self::assertSame( $changed_post, $returned );
	}

	#[Test]
	public function handle_returns_wp_error_when_event_rejects(): void {

		$this->bridge->register();

		$prepared_post = new stdClass();
		$request = Mockery::mock( WP_REST_Request::class );

		$error = new WP_Error( 'fundrik_rejected', 'Rejected.', [ 'foo' => 'bar' ] );

		$this->dispatcher
			->shouldReceive( 'dispatch' )
			->once()
			->with(
				Mockery::on(
					function ( object $event ) use ( $prepared_post, $request, $error ): bool {

						if ( ! $event instanceof FilterCampaignBeforeSavedViaRestEvent ) {
							return false;
						}

						if ( $event->prepared_post !== $prepared_post ) {
							return false;
						}

						if ( $event->request !== $request || $event->context !== $this->context ) {
							return false;
						}

						// Simulate listeners rejecting the operation.
						$event->reject( $error );

						return true;
					},
				),
			);

		$returned = $this->bridge->handle( $prepared_post, $request );

		self::assertSame( $error, $returned );
	}

	#[Test]
	public function handle_logs_and_returns_original_when_prepared_post_is_invalid(): void {

		$this->bridge->register();

		$post_type = $this->post_type_id_reader->get_id( CampaignPostType::class );
		$hook_name = 'rest_pre_insert_' . $post_type;

		$prepared_post = 'invalid-post';
		$request = Mockery::mock( WP_REST_Request::class );

		$this->dispatcher
			->shouldNotReceive( 'dispatch' );

		$this->psr_logger
			->shouldReceive( 'warning' )
			->once()
			->with(
				"Invalid \$prepared_post argument in '{$hook_name}' hook.",
				Mockery::subset(
					[
						'operation' => 'validate_hook_bridge',
						'outcome' => 'invalid',
						'invalid_argument' => 'prepared_post',
						'invoked' => false,
					],
				),
			);

		$returned = $this->bridge->handle( $prepared_post, $request );

		self::assertSame( $prepared_post, $returned );
	}

	#[Test]
	public function handle_logs_and_returns_original_when_request_is_invalid(): void {

		$this->bridge->register();

		$post_type = $this->post_type_id_reader->get_id( CampaignPostType::class );
		$hook_name = 'rest_pre_insert_' . $post_type;

		$prepared_post = new stdClass();
		$request = 'invalid-request';

		$this->dispatcher
			->shouldNotReceive( 'dispatch' );

		$this->psr_logger
			->shouldReceive( 'warning' )
			->once()
			->with(
				"Invalid \$request argument in '{$hook_name}' hook.",
				Mockery::subset(
					[
						'operation' => 'validate_hook_bridge',
						'outcome' => 'invalid',
						'invalid_argument' => 'request',
						'invoked' => false,
					],
				),
			);

		$returned = $this->bridge->handle( $prepared_post, $request );

		self::assertSame( $prepared_post, $returned );
	}

	#[Test]
	public function handle_logs_and_rethrows_when_dispatch_fails(): void {

		$this->bridge->register();

		$post_type = $this->post_type_id_reader->get_id( CampaignPostType::class );
		$hook_name = 'rest_pre_insert_' . $post_type;

		$prepared_post = new stdClass();
		$request = Mockery::mock( WP_REST_Request::class );

		$e = new RuntimeException( 'Dispatch failed' );

		$this->dispatcher
			->shouldReceive( 'dispatch' )
			->once()
			->with( Mockery::type( FilterCampaignBeforeSavedViaRestEvent::class ) )
			->andThrow( $e );

		$this->psr_logger
			->shouldReceive( 'error' )
			->once()
			->with(
				"Bridge dispatch failed for hook '{$hook_name}'.",
				Mockery::subset(
					[
						'operation' => 'dispatch_hook_bridge',
						'outcome' => 'error',
						'invoked' => true,
						'exception' => $e,
					],
				),
			);

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Dispatch failed' );

		$this->bridge->handle( $prepared_post, $request );
	}
}
