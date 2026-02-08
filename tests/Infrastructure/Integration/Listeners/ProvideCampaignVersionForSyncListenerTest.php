<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Listeners;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Domain\CampaignFactory;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;
use Fundrik\WordPress\Integration\Events\FilterCampaignRestResponseEvent;
use Fundrik\WordPress\Integration\Listeners\ProvideCampaignVersionForSyncListener;
use Fundrik\WordPress\Integration\PostTypes\CampaignPostType;
use Fundrik\WordPress\Integration\WordPressContext\WordPressContextInterface;
use Fundrik\WordPress\Tests\Fixtures\FakeCampaignRepositoryException;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use WP_Post;
use WP_REST_Request;
use WP_REST_Response;

#[CoversClass( ProvideCampaignVersionForSyncListener::class )]
#[UsesClass( FilterCampaignRestResponseEvent::class )]
final class ProvideCampaignVersionForSyncListenerTest extends MockeryTestCase {

	private CampaignRepositoryPort&MockInterface $campaign_repository;

	private CampaignFactory $campaign_factory;

	private WordPressContextInterface&MockInterface $context;
	private WP_REST_Request&MockInterface $request;

	private ProvideCampaignVersionForSyncListener $listener;

	protected function setUp(): void {

		parent::setUp();

		$this->campaign_repository = Mockery::mock( CampaignRepositoryPort::class );

		$this->campaign_factory = new CampaignFactory();

		$this->context = Mockery::mock( WordPressContextInterface::class );
		$this->request = Mockery::mock( WP_REST_Request::class );

		$this->listener = new ProvideCampaignVersionForSyncListener( $this->campaign_repository );
	}

	#[Test]
	public function handle_returns_early_when_repository_throws(): void {

		$post = $this->make_post_with_id( 7 );
		$response = $this->make_response_with_meta( [] );

		$this->campaign_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->with( Mockery::type( EntityId::class ) )
			->andThrow( new FakeCampaignRepositoryException( 'DB failed.' ) );

		$event = new FilterCampaignRestResponseEvent(
			response: $response,
			post: $post,
			request: $this->request,
			context: $this->context,
		);

		$this->listener->handle( $event );

		self::assertSame( [], $event->response->data['meta'] );
	}

	#[Test]
	public function handle_returns_early_when_campaign_is_not_found(): void {

		$post = $this->make_post_with_id( 7 );
		$response = $this->make_response_with_meta( [ 'foo' => 'bar' ] );

		$this->campaign_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->andReturn( null );

		$event = new FilterCampaignRestResponseEvent(
			response: $response,
			post: $post,
			request: $this->request,
			context: $this->context,
		);

		$this->listener->handle( $event );

		self::assertSame( [ 'foo' => 'bar' ], $event->response->data['meta'] );
	}

	#[Test]
	public function handle_initializes_meta_when_missing_and_sets_version(): void {

		$post = $this->make_post_with_id( 7 );

		$response = Mockery::mock( WP_REST_Response::class );
		$response->data = []; // meta missing.

		$campaign = $this->campaign_factory->create(
			id: 7,
			version: 3,
			title: 'Hello',
			is_active: true,
			is_open: true,
			has_target: false,
			target_amount: 0,
		);

		$this->campaign_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->andReturn( $campaign );

		$event = new FilterCampaignRestResponseEvent(
			response: $response,
			post: $post,
			request: $this->request,
			context: $this->context,
		);

		$this->listener->handle( $event );

		self::assertIsArray( $event->response->data['meta'] );
		self::assertSame( 3, $event->response->data['meta'][ CampaignPostType::ENTITY_VERSION_NAME ] );
	}

	#[Test]
	public function handle_overwrites_meta_when_meta_is_not_array_and_sets_version(): void {

		$post = $this->make_post_with_id( 7 );

		$response = Mockery::mock( WP_REST_Response::class );
		$response->data = [
			'meta' => 'not-an-array',
		];

		$campaign = $this->campaign_factory->create(
			id: 7,
			version: EntityVersion::create( 5 ),
			title: 'Hello',
			is_active: true,
			is_open: true,
			has_target: false,
			target_amount: 0,
		);

		$this->campaign_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->andReturn( $campaign );

		$event = new FilterCampaignRestResponseEvent(
			response: $response,
			post: $post,
			request: $this->request,
			context: $this->context,
		);

		$this->listener->handle( $event );

		self::assertIsArray( $event->response->data['meta'] );
		self::assertSame( 5, $event->response->data['meta'][ CampaignPostType::ENTITY_VERSION_NAME ] );
	}

	#[Test]
	public function handle_preserves_existing_meta_and_adds_version(): void {

		$post = $this->make_post_with_id( 7 );
		$response = $this->make_response_with_meta( [ 'foo' => 'bar' ] );

		$campaign = $this->campaign_factory->create(
			id: 7,
			version: 4,
			title: 'Hello',
			is_active: true,
			is_open: true,
			has_target: false,
			target_amount: 0,
		);

		$this->campaign_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->andReturn( $campaign );

		$event = new FilterCampaignRestResponseEvent(
			response: $response,
			post: $post,
			request: $this->request,
			context: $this->context,
		);

		$this->listener->handle( $event );

		self::assertSame( 'bar', $event->response->data['meta']['foo'] );
		self::assertSame( 4, $event->response->data['meta'][ CampaignPostType::ENTITY_VERSION_NAME ] );
	}

	private function make_post_with_id( int $id ): WP_Post {

		$post = Mockery::mock( WP_Post::class );
		$post->ID = $id;

		return $post;
	}

	private function make_response_with_meta( array $meta ): WP_REST_Response {

		$response = Mockery::mock( WP_REST_Response::class );
		$response->data = [
			'meta' => $meta,
		];

		return $response;
	}
}
