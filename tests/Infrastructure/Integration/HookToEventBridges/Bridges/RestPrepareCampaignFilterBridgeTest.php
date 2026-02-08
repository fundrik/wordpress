<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\HookToEventBridges\Bridges;

use Fundrik\WordPress\Infrastructure\EventDispatcher\InfrastructureEventDispatcherInterface;
use Fundrik\WordPress\Integration\Events\FilterCampaignRestResponseEvent;
use Fundrik\WordPress\Integration\HookToEventBridges\BridgeLogger;
use Fundrik\WordPress\Integration\HookToEventBridges\Bridges\RestPrepareCampaignFilterBridge;
use Fundrik\WordPress\Integration\HookToEventBridges\InvalidBridgeArgumentException;
use Fundrik\WordPress\Integration\PostTypes\Attributes\PostTypeId;
use Fundrik\WordPress\Integration\PostTypes\Attributes\PostTypeIdReader;
use Fundrik\WordPress\Integration\PostTypes\CampaignPostType;
use Fundrik\WordPress\Integration\WordPressContext\WordPressContextInterface;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use Psr\Log\LoggerInterface;
use RuntimeException;
use WP_Post;
use WP_REST_Request;
use WP_REST_Response;

#[CoversClass( RestPrepareCampaignFilterBridge::class )]
#[UsesClass( FilterCampaignRestResponseEvent::class )]
#[UsesClass( BridgeLogger::class )]
#[UsesClass( InvalidBridgeArgumentException::class )]
#[UsesClass( PostTypeId::class )]
#[UsesClass( PostTypeIdReader::class )]
final class RestPrepareCampaignFilterBridgeTest extends WordPressTestCase {

	private WordPressContextInterface&MockInterface $context;
	private InfrastructureEventDispatcherInterface&MockInterface $dispatcher;

	private LoggerInterface&MockInterface $psr_logger;
	private BridgeLogger $logger;

	private PostTypeIdReader $post_type_id_reader;

	private RestPrepareCampaignFilterBridge $bridge;

	protected function setUp(): void {

		parent::setUp();

		$this->context = Mockery::mock( WordPressContextInterface::class );
		$this->dispatcher = Mockery::mock( InfrastructureEventDispatcherInterface::class );

		$this->psr_logger = Mockery::mock( LoggerInterface::class )->shouldIgnoreMissing();
		$this->logger = new BridgeLogger( $this->psr_logger );

		$this->post_type_id_reader = new PostTypeIdReader();

		$this->bridge = new RestPrepareCampaignFilterBridge(
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
		$hook_name = 'rest_prepare_' . $post_type;

		self::assertSame( 10, has_filter( $hook_name, $this->bridge->handle( ... ) ) );
	}

	#[Test]
	public function handle_dispatches_event_and_returns_original_when_unchanged(): void {

		$this->bridge->register();

		$response = Mockery::mock( WP_REST_Response::class );
		$post = Mockery::mock( WP_Post::class );
		$request = Mockery::mock( WP_REST_Request::class );

		$this->dispatcher
			->shouldReceive( 'dispatch' )
			->once()
			->with(
				Mockery::on(
					function ( object $event ) use ( $response, $post, $request ): bool {

						if ( ! $event instanceof FilterCampaignRestResponseEvent ) {
							return false;
						}

						return $event->response === $response
							&& $event->post === $post
							&& $event->request === $request
							&& $event->context === $this->context;
					},
				),
			);

		$returned = $this->bridge->handle( $response, $post, $request );

		self::assertSame( $response, $returned );
	}

	#[Test]
	public function handle_dispatches_event_and_returns_modified_value_from_event(): void {

		$this->bridge->register();

		$response = Mockery::mock( WP_REST_Response::class );
		$post = Mockery::mock( WP_Post::class );
		$request = Mockery::mock( WP_REST_Request::class );

		$changed_response = Mockery::mock( WP_REST_Response::class );

		$this->dispatcher
			->shouldReceive( 'dispatch' )
			->once()
			->with(
				Mockery::on(
					function ( object $event ) use ( $response, $post, $request, $changed_response ): bool {

						if ( ! $event instanceof FilterCampaignRestResponseEvent ) {
							return false;
						}

						if ( $event->response !== $response ) {
							return false;
						}

						if (
							$event->post !== $post
							|| $event->request !== $request
							|| $event->context !== $this->context
						) {
							return false;
						}

						// Simulate listeners changing the response.
						$event->response = $changed_response;

						return true;
					},
				),
			);

		$returned = $this->bridge->handle( $response, $post, $request );

		self::assertSame( $changed_response, $returned );
	}

	#[Test]
	public function handle_logs_and_returns_original_when_response_is_invalid(): void {

		$this->bridge->register();

		$post_type = $this->post_type_id_reader->get_id( CampaignPostType::class );
		$hook_name = 'rest_prepare_' . $post_type;

		$response = 'invalid-response';
		$post = Mockery::mock( WP_Post::class );
		$request = Mockery::mock( WP_REST_Request::class );

		$this->dispatcher
			->shouldNotReceive( 'dispatch' );

		$this->psr_logger
			->shouldReceive( 'warning' )
			->once()
			->with(
				"Invalid \$response argument in '{$hook_name}' hook.",
				Mockery::subset(
					[
						'operation' => 'validate_hook_bridge',
						'outcome' => 'invalid',
						'invalid_argument' => 'response',
						'invoked' => false,
					],
				),
			);

		$returned = $this->bridge->handle( $response, $post, $request );

		self::assertSame( $response, $returned );
	}

	#[Test]
	public function handle_logs_and_returns_original_when_post_is_invalid(): void {

		$this->bridge->register();

		$post_type = $this->post_type_id_reader->get_id( CampaignPostType::class );
		$hook_name = 'rest_prepare_' . $post_type;

		$response = Mockery::mock( WP_REST_Response::class );
		$post = 'invalid-post';
		$request = Mockery::mock( WP_REST_Request::class );

		$this->dispatcher
			->shouldNotReceive( 'dispatch' );

		$this->psr_logger
			->shouldReceive( 'warning' )
			->once()
			->with(
				"Invalid \$post argument in '{$hook_name}' hook.",
				Mockery::subset(
					[
						'operation' => 'validate_hook_bridge',
						'outcome' => 'invalid',
						'invalid_argument' => 'post',
						'invoked' => false,
					],
				),
			);

		$returned = $this->bridge->handle( $response, $post, $request );

		self::assertSame( $response, $returned );
	}

	#[Test]
	public function handle_logs_and_returns_original_when_request_is_invalid(): void {

		$this->bridge->register();

		$post_type = $this->post_type_id_reader->get_id( CampaignPostType::class );
		$hook_name = 'rest_prepare_' . $post_type;

		$response = Mockery::mock( WP_REST_Response::class );
		$post = Mockery::mock( WP_Post::class );
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

		$returned = $this->bridge->handle( $response, $post, $request );

		self::assertSame( $response, $returned );
	}

	#[Test]
	public function handle_logs_and_rethrows_when_dispatch_fails(): void {

		$this->bridge->register();

		$post_type = $this->post_type_id_reader->get_id( CampaignPostType::class );
		$hook_name = 'rest_prepare_' . $post_type;

		$response = Mockery::mock( WP_REST_Response::class );
		$post = Mockery::mock( WP_Post::class );
		$request = Mockery::mock( WP_REST_Request::class );

		$e = new RuntimeException( 'Dispatch failed' );

		$this->dispatcher
			->shouldReceive( 'dispatch' )
			->once()
			->with( Mockery::type( FilterCampaignRestResponseEvent::class ) )
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

		$this->bridge->handle( $response, $post, $request );
	}
}
