<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\SyncPostToCampaign;

use Brain\Monkey\Functions;
use Fundrik\WordPress\Integration\Helpers\Meta;
use Fundrik\WordPress\Integration\PostTypes\Configs\CampaignPostTypeConfig;
use Fundrik\WordPress\Integration\SyncPostToCampaign\RestAfterInsertCampaignSyncDataExtractor;
use Fundrik\WordPress\Integration\SyncPostToCampaign\RestCampaignSyncDataDto;
use Fundrik\WordPress\Tests\MockeryTestCase;
use InvalidArgumentException;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use WP_Post;
use WP_REST_Request;

#[CoversClass( RestAfterInsertCampaignSyncDataExtractor::class )]
#[UsesClass( RestCampaignSyncDataDto::class )]
#[UsesClass( Meta::class )]
final class RestAfterInsertCampaignSyncDataExtractorTest extends MockeryTestCase {

	private WP_REST_Request&MockInterface $request;

	private RestAfterInsertCampaignSyncDataExtractor $extractor;

	protected function setUp(): void {

		parent::setUp();

		$this->request = Mockery::mock( WP_REST_Request::class );

		$this->extractor = new RestAfterInsertCampaignSyncDataExtractor();
	}

	#[Test]
	public function extract_throws_when_meta_payload_is_missing(): void {

		$post = $this->make_post( 10, 'Hello' );

		$this->request
			->shouldReceive( 'get_json_params' )
			->once()
			->andReturn( [] ); // no meta

		Functions\expect( 'metadata_exists' )
			->once()
			->with( 'post', 10, CampaignPostTypeConfig::META_IS_OPEN )
			->andReturn( false );

		Functions\expect( 'metadata_exists' )
			->once()
			->with( 'post', 10, CampaignPostTypeConfig::META_HAS_TARGET )
			->andReturn( false );

		Functions\expect( 'metadata_exists' )
			->once()
			->with( 'post', 10, CampaignPostTypeConfig::META_TARGET_AMOUNT )
			->andReturn( false );

		Functions\expect( 'get_post_meta' )->never();

		$this->expectException( InvalidArgumentException::class );

		$this->extractor->extract( $post, $this->request );
	}

	#[Test]
	public function extract_builds_dto_from_post_and_request_and_post_meta(): void {

		$post = $this->make_post( 10, 'Campaign title' );

		$this->request
			->shouldReceive( 'get_json_params' )
			->once()
			->andReturn(
				[
					'meta' => [
						CampaignPostTypeConfig::ENTITY_VERSION_FIELD_NAME => 7,
					],
				],
			);

		Functions\expect( 'metadata_exists' )
			->once()
			->with( 'post', 10, CampaignPostTypeConfig::META_IS_OPEN )
			->andReturn( true );

		Functions\expect( 'get_post_meta' )
			->once()
			->with( 10, CampaignPostTypeConfig::META_IS_OPEN, true )
			->andReturn( '0' );

		Functions\expect( 'metadata_exists' )
			->once()
			->with( 'post', 10, CampaignPostTypeConfig::META_HAS_TARGET )
			->andReturn( true );

		Functions\expect( 'get_post_meta' )
			->once()
			->with( 10, CampaignPostTypeConfig::META_HAS_TARGET, true )
			->andReturn( '1' );

		Functions\expect( 'metadata_exists' )
			->once()
			->with( 'post', 10, CampaignPostTypeConfig::META_TARGET_AMOUNT )
			->andReturn( true );

		Functions\expect( 'get_post_meta' )
			->once()
			->with( 10, CampaignPostTypeConfig::META_TARGET_AMOUNT, true )
			->andReturn( '123' );

		$result = $this->extractor->extract( $post, $this->request );

		self::assertInstanceOf( RestCampaignSyncDataDto::class, $result );

		self::assertSame( 10, $result->id->get_value() );
		self::assertSame( 'Campaign title', $result->title );
		self::assertSame( 7, $result->version->get_value() );

		self::assertTrue( $result->is_active );
		self::assertFalse( $result->is_open );
		self::assertTrue( $result->has_target );
		self::assertSame( 123, $result->target_amount );
	}

	#[Test]
	public function extract_applies_defaults_when_meta_keys_do_not_exist(): void {

		$post = $this->make_post( 10, 'Campaign title' );

		$this->request
			->shouldReceive( 'get_json_params' )
			->once()
			->andReturn(
				[
					'meta' => [
						CampaignPostTypeConfig::ENTITY_VERSION_FIELD_NAME => 2,
					],
				],
			);

		Functions\expect( 'metadata_exists' )
			->once()
			->with( 'post', 10, CampaignPostTypeConfig::META_IS_OPEN )
			->andReturn( false );

		Functions\expect( 'metadata_exists' )
			->once()
			->with( 'post', 10, CampaignPostTypeConfig::META_HAS_TARGET )
			->andReturn( false );

		Functions\expect( 'metadata_exists' )
			->once()
			->with( 'post', 10, CampaignPostTypeConfig::META_TARGET_AMOUNT )
			->andReturn( false );

		Functions\expect( 'get_post_meta' )->never();

		$result = $this->extractor->extract( $post, $this->request );

		self::assertSame( 10, $result->id->get_value() );
		self::assertSame( 'Campaign title', $result->title );
		self::assertSame( 2, $result->version->get_value() );

		self::assertTrue( $result->is_active );
		// Defaults in extractor:
		// - META_IS_OPEN defaults to '1' -> true
		// - META_HAS_TARGET defaults to '0' -> false
		// - META_TARGET_AMOUNT defaults to '0' -> 0
		self::assertTrue( $result->is_open );
		self::assertFalse( $result->has_target );
		self::assertSame( 0, $result->target_amount );
	}

	#[Test]
	public function extract_normalizes_empty_string_boolean_meta_to_false(): void {

		$post = $this->make_post( 10, 'Campaign title' );

		$this->request
			->shouldReceive( 'get_json_params' )
			->once()
			->andReturn(
				[
					'meta' => [
						CampaignPostTypeConfig::ENTITY_VERSION_FIELD_NAME => 2,
					],
				],
			);

		Functions\expect( 'metadata_exists' )
			->once()
			->with( 'post', 10, CampaignPostTypeConfig::META_IS_OPEN )
			->andReturn( true );

		Functions\expect( 'get_post_meta' )
			->once()
			->with( 10, CampaignPostTypeConfig::META_IS_OPEN, true )
			->andReturn( '' ); // WordPress "false"

		Functions\expect( 'metadata_exists' )
			->once()
			->with( 'post', 10, CampaignPostTypeConfig::META_HAS_TARGET )
			->andReturn( true );

		Functions\expect( 'get_post_meta' )
			->once()
			->with( 10, CampaignPostTypeConfig::META_HAS_TARGET, true )
			->andReturn( '' ); // WordPress "false"

		Functions\expect( 'metadata_exists' )
			->once()
			->with( 'post', 10, CampaignPostTypeConfig::META_TARGET_AMOUNT )
			->andReturn( true );

		Functions\expect( 'get_post_meta' )
			->once()
			->with( 10, CampaignPostTypeConfig::META_TARGET_AMOUNT, true )
			->andReturn( '0' );

		$result = $this->extractor->extract( $post, $this->request );

		self::assertTrue( $result->is_active );
		self::assertFalse( $result->is_open );
		self::assertFalse( $result->has_target );
		self::assertSame( 0, $result->target_amount );
	}

	#[Test]
	public function extract_sets_campaign_inactive_when_post_status_is_not_publish(): void {

		$post = $this->make_post( 10, 'Campaign title', 'draft' );

		$this->request
			->shouldReceive( 'get_json_params' )
			->once()
			->andReturn(
				[
					'meta' => [
						CampaignPostTypeConfig::ENTITY_VERSION_FIELD_NAME => 2,
					],
				],
			);

		Functions\expect( 'metadata_exists' )
			->once()
			->with( 'post', 10, CampaignPostTypeConfig::META_IS_OPEN )
			->andReturn( false );

		Functions\expect( 'metadata_exists' )
			->once()
			->with( 'post', 10, CampaignPostTypeConfig::META_HAS_TARGET )
			->andReturn( false );

		Functions\expect( 'metadata_exists' )
			->once()
			->with( 'post', 10, CampaignPostTypeConfig::META_TARGET_AMOUNT )
			->andReturn( false );

		Functions\expect( 'get_post_meta' )->never();

		$result = $this->extractor->extract( $post, $this->request );

		self::assertFalse( $result->is_active );
	}

	private function make_post( int $id, string $title, string $status = 'publish' ): WP_Post {

		$post = Mockery::mock( WP_Post::class );
		$post->ID = $id;
		$post->post_title = $title;
		$post->post_status = $status;

		return $post;
	}
}
