<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\SyncPostToCampaign;

use Fundrik\Core\Components\Shared\Domain\EntityVersion;
use Fundrik\WordPress\Components\Campaigns\Domain\CampaignId;
use Fundrik\WordPress\Integration\AdminSettings\Groups\GeneralSettingsGroup;
use Fundrik\WordPress\Integration\AdminSettings\Settings\General\CurrencySetting;
use Fundrik\WordPress\Integration\PostTypes\Configs\CampaignPostTypeConfig;
use Fundrik\WordPress\Integration\SyncPostToCampaign\RestCampaignSyncData;
use Fundrik\WordPress\Integration\SyncPostToCampaign\RestPreInsertCampaignSyncDataExtractor;
use Brain\Monkey\Functions;
use Fundrik\WordPress\Infrastructure\Ports\Storage\StoragePort;
use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsFieldRenderer;
use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsReader;
use Fundrik\WordPress\Integration\AdminSettings\Groups\CampaignSettingsGroup;
use Fundrik\WordPress\Integration\AdminSettings\Settings\Campaign\CampaignDefaultAcceptsDonationsSetting;
use Fundrik\WordPress\Integration\AdminSettings\Settings\Campaign\CampaignDefaultHasTargetSetting;
use Fundrik\WordPress\Integration\Helpers\OptionReader;
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
#[UsesClass( RestCampaignSyncData::class )]
#[UsesClass( AdminSettingsReader::class )]
#[UsesClass( CampaignSettingsGroup::class )]
#[UsesClass( CampaignDefaultAcceptsDonationsSetting::class )]
#[UsesClass( CampaignDefaultHasTargetSetting::class )]
#[UsesClass( GeneralSettingsGroup::class )]
#[UsesClass( CurrencySetting::class )]
final class RestPreInsertCampaignSyncDataExtractorTest extends MockeryTestCase {

	private WP_REST_Request&MockInterface $request;

	private RestPreInsertCampaignSyncDataExtractor $extractor;

	protected function setUp(): void {

		parent::setUp();

		$this->request = Mockery::mock( WP_REST_Request::class );

		$this->extractor = new RestPreInsertCampaignSyncDataExtractor(
			$this->create_settings_reader(),
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

		self::assertInstanceOf( RestCampaignSyncData::class, $result );

		self::assertInstanceOf( CampaignId::class, $result->id );
		self::assertSame( 10, $result->id->get_value() );

		self::assertSame( 'Persisted title', $result->title );

		self::assertInstanceOf( EntityVersion::class, $result->version );
		self::assertSame( 3, $result->version->get_value() );

		self::assertSame( true, $result->accepts_donations );
		self::assertSame( false, $result->has_target );
		self::assertNull( $result->target_amount );
		self::assertSame( 'RUB', $result->target_currency );
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

		self::assertInstanceOf( RestCampaignSyncData::class, $result );

		self::assertSame( 15, $result->id->get_value() );
		self::assertSame( 'Ok', $result->title );
		self::assertSame( 7, $result->version->get_value() );

		self::assertFalse( $result->accepts_donations );
		self::assertTrue( $result->has_target );
		self::assertSame( 12300, $result->target_amount );
		self::assertSame( 'USD', $result->target_currency );
	}

	#[Test]
	public function extract_or_error_returns_wp_error_when_entity_version_is_non_positive(): void {

		$prepared_post = new stdClass();

		$this->request
			->shouldReceive( 'get_json_params' )
			->once()
			->andReturn(
				[
					'id' => 15,
					'title' => 'Ok',
					'meta' => [
						CampaignPostTypeConfig::ENTITY_VERSION_FIELD_NAME => 0,
					],
				],
			);

		$result = $this->extractor->extract_or_error( $prepared_post, $this->request );

		self::assertInstanceOf( WP_Error::class, $result );
		self::assertSame( 'fundrik_campaign_invalid_payload', $result->get_error_code() );
		self::assertSame( 'Entity version must be a positive integer. Given: 0.', $result->get_error_message() );
		self::assertSame( 422, $result->get_error_data()['status'] );
	}

	#[Test]
	public function extract_or_error_ignores_target_amount_when_target_is_disabled(): void {

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
						CampaignPostTypeConfig::META_HAS_TARGET => false,
						CampaignPostTypeConfig::META_TARGET_AMOUNT => 123,
						CampaignPostTypeConfig::META_TARGET_CURRENCY => 'USD',
					],
				],
			);

		$result = $this->extractor->extract_or_error( $prepared_post, $this->request );

		self::assertInstanceOf( RestCampaignSyncData::class, $result );
		self::assertFalse( $result->has_target );
		self::assertNull( $result->target_amount );
		self::assertSame( 'USD', $result->target_currency );
	}

	#[Test]
	public function extract_or_error_treats_empty_target_amount_as_null(): void {

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
						CampaignPostTypeConfig::META_ACCEPTS_DONATIONS => true,
						CampaignPostTypeConfig::META_HAS_TARGET => true,
						CampaignPostTypeConfig::META_TARGET_AMOUNT => '',
						CampaignPostTypeConfig::META_TARGET_CURRENCY => 'USD',
					],
				],
			);

		$result = $this->extractor->extract_or_error( $prepared_post, $this->request );

		self::assertInstanceOf( RestCampaignSyncData::class, $result );
		self::assertTrue( $result->has_target );
		self::assertNull( $result->target_amount );
		self::assertSame( 'USD', $result->target_currency );
	}

	#[Test]
	public function extract_or_error_treats_empty_target_currency_as_default(): void {

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
						CampaignPostTypeConfig::META_TARGET_CURRENCY => '',
					],
				],
			);

		$result = $this->extractor->extract_or_error( $prepared_post, $this->request );

		self::assertInstanceOf( RestCampaignSyncData::class, $result );
		self::assertSame( 'RUB', $result->target_currency );
	}

	private function create_settings_reader(
		bool $default_accepts_donations = true,
		bool $default_has_target = false,
		string $currency = 'RUB',
	): AdminSettingsReader {

		$storage = Mockery::mock( StoragePort::class );
		$storage
			->shouldReceive( 'get' )
			->zeroOrMoreTimes()
			->with( 'fundrik_general_currency_setting' )
			->andReturn( $currency );
		$storage
			->shouldReceive( 'get' )
			->zeroOrMoreTimes()
			->with( 'fundrik_campaign_default_accepts_donations_setting' )
			->andReturn( $default_accepts_donations );
		$storage
			->shouldReceive( 'get' )
			->zeroOrMoreTimes()
			->with( 'fundrik_campaign_default_has_target_setting' )
			->andReturn( $default_has_target );

		$field_renderer = new AdminSettingsFieldRenderer();

		return new AdminSettingsReader(
			new OptionReader( $storage ),
			new GeneralSettingsGroup(
				new CurrencySetting( $field_renderer ),
			),
			new CampaignSettingsGroup(
				new CampaignDefaultAcceptsDonationsSetting( $field_renderer ),
				new CampaignDefaultHasTargetSetting( $field_renderer ),
			),
		);
	}
}



