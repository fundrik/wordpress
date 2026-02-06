<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\Integration\Listeners;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Domain\CampaignFactory;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\WordPress\Infrastructure\Integration\Events\FilterCampaignBeforeSavedViaRestEvent;
use Fundrik\WordPress\Infrastructure\Integration\Listeners\EnsureCampaignPostCanBeSyncedListener;
use Fundrik\WordPress\Infrastructure\Integration\PostTypes\CampaignPostType;
use Fundrik\WordPress\Infrastructure\Integration\WordPressContext\WordPressContextInterface;
use Fundrik\WordPress\Tests\Fixtures\FakeCampaignRepositoryException;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use stdClass;
use WP_Error;
use WP_REST_Request;

#[CoversClass( EnsureCampaignPostCanBeSyncedListener::class )]
#[UsesClass( FilterCampaignBeforeSavedViaRestEvent::class )]
final class EnsureCampaignPostCanBeSyncedListenerTest extends MockeryTestCase {

	private CampaignFactory $campaign_factory;
	private CampaignRepositoryPort&MockInterface $campaign_repository;

	private WordPressContextInterface&MockInterface $context;
	private WP_REST_Request&MockInterface $request;

	private EnsureCampaignPostCanBeSyncedListener $listener;

	protected function setUp(): void {

		parent::setUp();

		$this->campaign_factory = new CampaignFactory();
		$this->campaign_repository = Mockery::mock( CampaignRepositoryPort::class );

		$this->context = Mockery::mock( WordPressContextInterface::class );
		$this->request = Mockery::mock( WP_REST_Request::class );

		$this->listener = new EnsureCampaignPostCanBeSyncedListener(
			$this->campaign_factory,
			$this->campaign_repository,
		);
	}

	#[Test]
	public function handle_rejects_when_payload_is_invalid(): void {

		$prepared_post = $this->make_prepared_post_with_id( 10 );

		$this->request
			->shouldReceive( 'get_json_params' )
			->once()
			->andReturn(
				[
					'meta' => [
						// Invalid type for bool.
						CampaignPostType::META_IS_OPEN => 'not-a-bool',
					],
				],
			);

		$event = new FilterCampaignBeforeSavedViaRestEvent(
			prepared_post: $prepared_post,
			request: $this->request,
			context: $this->context,
		);

		$this->campaign_repository->shouldNotReceive( 'find_by_id' );

		$this->listener->handle( $event );

		self::assertTrue( $event->is_rejected() );

		$error = $event->get_rejection_error();
		self::assertInstanceOf( WP_Error::class, $error );
		self::assertSame( 'fundrik_campaign_invalid_payload', $error->get_error_code() );
		self::assertSame( 422, $error->get_error_data()['status'] );
	}

	#[Test]
	public function handle_returns_early_when_expected_version_is_missing(): void {

		$prepared_post = $this->make_prepared_post_with_id( 10 );

		$this->request
			->shouldReceive( 'get_json_params' )
			->once()
			->andReturn(
				[
					'id' => 10,
					'title' => 'Ok',
					'meta' => [
						// expected_version missing intentionally.
						CampaignPostType::META_IS_OPEN => true,
						CampaignPostType::META_HAS_TARGET => false,
						CampaignPostType::META_TARGET_AMOUNT => 0,
					],
				],
			);

		$this->campaign_repository->shouldNotReceive( 'find_by_id' );

		$event = new FilterCampaignBeforeSavedViaRestEvent(
			prepared_post: $prepared_post,
			request: $this->request,
			context: $this->context,
		);

		$this->listener->handle( $event );

		self::assertFalse( $event->is_rejected() );
		self::assertNull( $event->get_rejection_error() );
	}

	#[Test]
	public function handle_rejects_when_repository_lookup_fails(): void {

		$prepared_post = $this->make_prepared_post_with_id( 10 );

		$this->request
			->shouldReceive( 'get_json_params' )
			->once()
			->andReturn(
				[
					'id' => 10,
					'title' => 'Ok',
					'meta' => [
						CampaignPostType::ENTITY_VERSION_NAME => 3,
						CampaignPostType::META_IS_OPEN => true,
						CampaignPostType::META_HAS_TARGET => false,
						CampaignPostType::META_TARGET_AMOUNT => 0,
					],
				],
			);

		$repo_exception = new FakeCampaignRepositoryException( 'DB error.' );

		$this->campaign_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->with( Mockery::on( static fn ( EntityId $id ): bool => $id->get_value() === 10 ) )
			->andThrow( $repo_exception );

		$event = new FilterCampaignBeforeSavedViaRestEvent(
			prepared_post: $prepared_post,
			request: $this->request,
			context: $this->context,
		);

		$this->listener->handle( $event );

		self::assertTrue( $event->is_rejected() );

		$error = $event->get_rejection_error();
		self::assertInstanceOf( WP_Error::class, $error );
		self::assertSame( 'fundrik_campaign_version_check_failed', $error->get_error_code() );
		self::assertSame( 500, $error->get_error_data()['status'] );
	}

	#[Test]
	public function handle_rejects_when_campaign_is_not_found_for_version_check(): void {

		$prepared_post = $this->make_prepared_post_with_id( 10 );

		$this->request
			->shouldReceive( 'get_json_params' )
			->once()
			->andReturn(
				[
					'id' => 10,
					'title' => 'Ok',
					'meta' => [
						CampaignPostType::ENTITY_VERSION_NAME => 3,
						CampaignPostType::META_IS_OPEN => true,
						CampaignPostType::META_HAS_TARGET => false,
						CampaignPostType::META_TARGET_AMOUNT => 0,
					],
				],
			);

		$this->campaign_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->andReturn( null );

		$event = new FilterCampaignBeforeSavedViaRestEvent(
			prepared_post: $prepared_post,
			request: $this->request,
			context: $this->context,
		);

		$this->listener->handle( $event );

		self::assertTrue( $event->is_rejected() );

		$error = $event->get_rejection_error();
		self::assertInstanceOf( WP_Error::class, $error );
		self::assertSame( 'fundrik_campaign_not_found', $error->get_error_code() );
		self::assertSame( 409, $error->get_error_data()['status'] );
	}

	#[Test]
	public function handle_rejects_when_versions_do_not_match(): void {

		$prepared_post = $this->make_prepared_post_with_id( 10 );

		$this->request
			->shouldReceive( 'get_json_params' )
			->once()
			->andReturn(
				[
					'id' => 10,
					'title' => 'Ok',
					'meta' => [
						CampaignPostType::ENTITY_VERSION_NAME => 3,
						CampaignPostType::META_IS_OPEN => true,
						CampaignPostType::META_HAS_TARGET => false,
						CampaignPostType::META_TARGET_AMOUNT => 0,
					],
				],
			);

		$persisted = $this->campaign_factory->create(
			id: 10,
			version: 5,
			title: 'Persisted',
			is_active: true,
			is_open: true,
			has_target: false,
			target_amount: 0,
		);

		$this->campaign_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->andReturn( $persisted );

		$event = new FilterCampaignBeforeSavedViaRestEvent(
			prepared_post: $prepared_post,
			request: $this->request,
			context: $this->context,
		);

		$this->listener->handle( $event );

		self::assertTrue( $event->is_rejected() );

		$error = $event->get_rejection_error();
		self::assertInstanceOf( WP_Error::class, $error );

		self::assertSame( 'fundrik_campaign_version_mismatch', $error->get_error_code() );

		$data = $error->get_error_data();
		self::assertSame( 409, $data['status'] );
		self::assertSame( 3, $data['expected_version'] );
		self::assertSame( 5, $data['current_version'] );
	}

	#[Test]
	public function handle_accepts_when_versions_match(): void {

		$prepared_post = $this->make_prepared_post_with_id( 10 );

		$this->request
			->shouldReceive( 'get_json_params' )
			->once()
			->andReturn(
				[
					'id' => 10,
					'title' => 'Ok',
					'meta' => [
						CampaignPostType::ENTITY_VERSION_NAME => 5,
						CampaignPostType::META_IS_OPEN => true,
						CampaignPostType::META_HAS_TARGET => false,
						CampaignPostType::META_TARGET_AMOUNT => 0,
					],
				],
			);

		$persisted = $this->campaign_factory->create(
			id: 10,
			version: 5,
			title: 'Persisted',
			is_active: true,
			is_open: true,
			has_target: false,
			target_amount: 0,
		);

		$this->campaign_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->andReturn( $persisted );

		$event = new FilterCampaignBeforeSavedViaRestEvent(
			prepared_post: $prepared_post,
			request: $this->request,
			context: $this->context,
		);

		$this->listener->handle( $event );

		self::assertFalse( $event->is_rejected() );
		self::assertNull( $event->get_rejection_error() );
	}

	#[Test]
	public function handle_rejects_when_domain_rejects_payload(): void {

		$prepared_post = $this->make_prepared_post_with_id( 10 );

		$this->request
			->shouldReceive( 'get_json_params' )
			->once()
			->andReturn(
				[
					'id' => 10,
					'title' => '',
					'meta' => [
						CampaignPostType::META_IS_OPEN => true,
						CampaignPostType::META_HAS_TARGET => false,
						CampaignPostType::META_TARGET_AMOUNT => 0,
					],
				],
			);

		$this->campaign_repository->shouldNotReceive( 'find_by_id' );

		$event = new FilterCampaignBeforeSavedViaRestEvent(
			prepared_post: $prepared_post,
			request: $this->request,
			context: $this->context,
		);

		$this->listener->handle( $event );

		self::assertTrue( $event->is_rejected() );

		$error = $event->get_rejection_error();
		self::assertInstanceOf( WP_Error::class, $error );
		self::assertSame( 'fundrik_campaign_validation_failed', $error->get_error_code() );
		self::assertSame( 422, $error->get_error_data()['status'] );
	}

	private function make_prepared_post_with_id( int $id ): stdClass {

		$post = new stdClass();
		$post->ID = $id;

		return $post;
	}
}
