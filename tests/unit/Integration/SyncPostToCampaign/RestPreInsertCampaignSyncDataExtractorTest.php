<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\SyncPostToCampaign;

use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;
use Fundrik\WordPress\Integration\PostTypes\Configs\CampaignPostTypeConfig;
use Fundrik\WordPress\Integration\PostTypes\PostTypeMetaFieldReader;
use Fundrik\WordPress\Integration\SyncPostToCampaign\RestCampaignSyncDataDto;
use Fundrik\WordPress\Integration\SyncPostToCampaign\RestPreInsertCampaignSyncDataExtractor;
use Brain\Monkey\Functions;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use stdClass;
use WP_Error;
use WP_REST_Request;

#[CoversClass( RestPreInsertCampaignSyncDataExtractor::class )]
#[UsesClass( RestCampaignSyncDataDto::class )]
#[UsesClass( PostTypeMetaFieldReader::class )]
final class RestPreInsertCampaignSyncDataExtractorTest extends MockeryTestCase {

	private WP_REST_Request&MockInterface $request;

	private RestPreInsertCampaignSyncDataExtractor $extractor;

	protected function setUp(): void {

		parent::setUp();

		$this->request = Mockery::mock( WP_REST_Request::class );

		$this->extractor = new RestPreInsertCampaignSyncDataExtractor(
			new PostTypeMetaFieldReader(),
		);
	}

	#[Test]
	public function extract_or_error_returns_wp_error_when_payload_is_invalid(): void {

		$prepared_post = new stdClass();

		$this->request
			->shouldReceive( 'get_json_params' )
			->once()
			->andReturn(
				[
					// Missing "id" and "meta".
					'title' => 'Hello',
				],
			);

		$result = $this->extractor->extract_or_error( $prepared_post, $this->request );

		self::assertInstanceOf( WP_Error::class, $result );
		self::assertSame( 'fundrik_campaign_invalid_payload', $result->get_error_code() );
		self::assertSame( 422, $result->get_error_data()['status'] );
	}

	#[Test]
	public function extract_or_error_returns_dto_and_applies_defaults_for_optional_fields(): void {

		$prepared_post = new stdClass();

		$this->request
			->shouldReceive( 'get_json_params' )
			->once()
			->andReturn(
				[
					'id' => 10,
					'meta' => [
						CampaignPostTypeConfig::ENTITY_VERSION_FIELD_NAME => 3,
						// meta flags missing -> defaults in extractor
					],
			],
			);

		Functions\expect( 'get_post_field' )
			->once()
			->with( 'post_title', 10, 'raw' )
			->andReturn( 'Persisted title' );

		$result = $this->extractor->extract_or_error( $prepared_post, $this->request );

		self::assertInstanceOf( RestCampaignSyncDataDto::class, $result );

		self::assertInstanceOf( EntityId::class, $result->id );
		self::assertSame( 10, $result->id->get_value() );

		self::assertSame( 'Persisted title', $result->title );

		self::assertInstanceOf( EntityVersion::class, $result->version );
		self::assertSame( 3, $result->version->get_value() );

		self::assertSame( true, $result->accepts_donations );
		self::assertSame( false, $result->has_target );
		self::assertNull( $result->target_amount );
		self::assertSame(
			( new PostTypeMetaFieldReader() )->get_meta_default_by_config_class(
				CampaignPostTypeConfig::class,
				CampaignPostTypeConfig::META_TARGET_CURRENCY,
			),
			$result->target_currency,
		);
	}

	#[Test]
	public function extract_or_error_returns_dto_with_explicit_values(): void {

		$prepared_post = new stdClass();

		$this->request
			->shouldReceive( 'get_json_params' )
			->once()
			->andReturn(
				[
					'id' => 15,
					'title' => 'Ok',
					'meta' => [
						CampaignPostTypeConfig::ENTITY_VERSION_FIELD_NAME => 7,
						CampaignPostTypeConfig::META_ACCEPTS_DONATIONS => false,
						CampaignPostTypeConfig::META_HAS_TARGET => true,
						CampaignPostTypeConfig::META_TARGET_AMOUNT => 123,
						CampaignPostTypeConfig::META_TARGET_CURRENCY => 'USD',
					],
				],
			);

		$result = $this->extractor->extract_or_error( $prepared_post, $this->request );

		self::assertInstanceOf( RestCampaignSyncDataDto::class, $result );

		self::assertSame( 15, $result->id->get_value() );
		self::assertSame( 'Ok', $result->title );
		self::assertSame( 7, $result->version->get_value() );

		self::assertFalse( $result->accepts_donations );
		self::assertTrue( $result->has_target );
		self::assertSame( 123, $result->target_amount );
		self::assertSame( 'USD', $result->target_currency );
	}

}


