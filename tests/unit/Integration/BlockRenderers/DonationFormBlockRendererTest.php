<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\BlockRenderers;

use Brain\Monkey\Functions;
use Fundrik\WordPress\Infrastructure\Ports\Storage\StoragePort;
use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsFieldRenderer;
use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsReader;
use Fundrik\WordPress\Integration\AdminSettings\Groups\CampaignSettingsGroup;
use Fundrik\WordPress\Integration\AdminSettings\Groups\DonationFormSettingsGroup;
use Fundrik\WordPress\Integration\AdminSettings\Groups\GeneralSettingsGroup;
use Fundrik\WordPress\Integration\AdminSettings\Settings\Campaign\CampaignDefaultAcceptsDonationsSetting;
use Fundrik\WordPress\Integration\AdminSettings\Settings\Campaign\CampaignDefaultHasTargetSetting;
use Fundrik\WordPress\Integration\AdminSettings\Settings\DonationForm\DonationFormDefaultAmountLabelSetting;
use Fundrik\WordPress\Integration\AdminSettings\Settings\DonationForm\DonationFormDefaultAmountSetting;
use Fundrik\WordPress\Integration\AdminSettings\Settings\General\CurrencySetting;
use Fundrik\WordPress\Integration\BlockRenderers\DonationFormBlockRenderer;
use Fundrik\WordPress\Integration\Helpers\OptionReader;
use Fundrik\WordPress\Integration\PostTypes\Configs\CampaignPostTypeConfig;
use Fundrik\WordPress\Integration\RestApi\Routes\DonationsRestRoute;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( DonationFormBlockRenderer::class )]
final class DonationFormBlockRendererTest extends WordPressTestCase {

	private const string DONATIONS_REST_BASE = DonationsRestRoute::ROUTE_NAMESPACE . DonationsRestRoute::ROUTE_PATH;
	private const string DONATIONS_REST_URL = 'http://example.test/wp-json/' . self::DONATIONS_REST_BASE;

	private function create_renderer(
		int $default_amount = 10,
		string $default_amount_label = 'Amount',
		bool $default_accepts_donations = true,
		bool $default_has_target = false,
	): DonationFormBlockRenderer {

		$settings_reader = $this->create_settings_reader(
			$default_amount,
			$default_amount_label,
			$default_accepts_donations,
			$default_has_target,
		);

		return new DonationFormBlockRenderer( $settings_reader );
	}

	private function create_settings_reader(
		int $default_amount,
		string $default_amount_label,
		bool $default_accepts_donations,
		bool $default_has_target,
	): AdminSettingsReader {

		$storage = Mockery::mock( StoragePort::class );
		$storage
			->shouldReceive( 'get' )
			->zeroOrMoreTimes()
			->with( 'fundrik_donation_form_default_amount_setting' )
			->andReturn( $default_amount );
		$storage
			->shouldReceive( 'get' )
			->zeroOrMoreTimes()
			->with( 'fundrik_donation_form_default_amount_label_setting' )
			->andReturn( $default_amount_label );
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
			new GeneralSettingsGroup( new CurrencySetting( $field_renderer ) ),
			new CampaignSettingsGroup(
				new CampaignDefaultAcceptsDonationsSetting( $field_renderer ),
				new CampaignDefaultHasTargetSetting( $field_renderer ),
			),
			new DonationFormSettingsGroup(
				new DonationFormDefaultAmountSetting( $field_renderer ),
				new DonationFormDefaultAmountLabelSetting( $field_renderer ),
			),
		);
	}

	private function expect_accepts_donations_meta( int $post_id, ?bool $accepts_donations ): void {

		Functions\expect( 'metadata_exists' )
			->once()
			->with( 'post', $post_id, CampaignPostTypeConfig::META_ACCEPTS_DONATIONS )
			->andReturn( $accepts_donations !== null );

		if ( $accepts_donations === null ) {
			Functions\expect( 'get_post_meta' )->never();
			return;
		}

		Functions\expect( 'get_post_meta' )
			->once()
			->with( $post_id, CampaignPostTypeConfig::META_ACCEPTS_DONATIONS, true )
			->andReturn( $accepts_donations ? '1' : '0' );
	}

	#[Test]
	public function render_returns_unavailable_markup_when_post_id_is_missing(): void {

		Functions\expect( 'get_the_ID' )->once()->andReturn( false );
		Functions\expect( 'get_queried_object_id' )->once()->andReturn( 0 );
		Functions\expect( 'wp_generate_uuid4' )->never();
		Functions\expect( 'rest_url' )->never();

		$renderer = $this->create_renderer();
		$markup = $renderer->render();

		self::assertStringContainsString( 'wp-block-fundrik-donation-form', $markup );
		self::assertStringContainsString( 'Donation form is unavailable on this page.', $markup );
	}

	#[Test]
	public function render_returns_empty_markup_when_campaign_does_not_accept_donations(): void {

		Functions\expect( 'get_the_ID' )->once()->andReturn( 42 );
		Functions\expect( 'get_queried_object_id' )->never();
		$this->expect_accepts_donations_meta( 42, false );
		Functions\expect( 'wp_generate_uuid4' )->never();
		Functions\expect( 'rest_url' )->never();

		$renderer = $this->create_renderer();

		self::assertSame( '', $renderer->render() );
	}

	#[Test]
	public function render_returns_form_markup_for_post_id_from_loop_context(): void {

		Functions\expect( 'get_the_ID' )->once()->andReturn( 42 );
		Functions\expect( 'get_queried_object_id' )->never();
		$this->expect_accepts_donations_meta( 42, null );
		Functions\expect( 'wp_generate_uuid4' )->once()->andReturn( '123e4567-e89b-12d3-a456-426614174042' );
		Functions\expect( 'rest_url' )
			->once()
			->with( self::DONATIONS_REST_BASE )
			->andReturn( self::DONATIONS_REST_URL );

		$renderer = $this->create_renderer( 10, 'Amount' );
		$markup = $renderer->render();

		self::assertStringContainsString( 'class="fundrik-donation-form"', $markup );
		self::assertStringContainsString( 'data-rest-url="' . self::DONATIONS_REST_URL . '"', $markup );
		self::assertStringContainsString( 'data-campaign-id="42"', $markup );
		self::assertStringContainsString( 'data-donation-id="123e4567-e89b-12d3-a456-426614174042"', $markup );
		self::assertStringContainsString( '>Amount</label>', $markup );
		self::assertStringContainsString( 'min="1"', $markup );
		self::assertStringContainsString( 'step="1"', $markup );
		self::assertStringContainsString( 'inputmode="numeric"', $markup );
		self::assertStringContainsString( 'value="10"', $markup );
	}

	#[Test]
	public function render_falls_back_to_queried_object_id_when_loop_post_id_is_missing(): void {

		Functions\expect( 'get_the_ID' )->once()->andReturn( false );
		Functions\expect( 'get_queried_object_id' )->once()->andReturn( 73 );
		$this->expect_accepts_donations_meta( 73, null );
		Functions\expect( 'wp_generate_uuid4' )->once()->andReturn( '123e4567-e89b-12d3-a456-426614174073' );
		Functions\expect( 'rest_url' )
			->once()
			->with( self::DONATIONS_REST_BASE )
			->andReturn( self::DONATIONS_REST_URL );

		$renderer = $this->create_renderer();
		$markup = $renderer->render();

		self::assertStringContainsString( 'data-campaign-id="73"', $markup );
	}

	#[Test]
	public function render_generates_fallback_donation_id_when_wordpress_returns_empty_uuid(): void {

		Functions\expect( 'get_the_ID' )->once()->andReturn( 11 );
		Functions\expect( 'get_queried_object_id' )->never();
		$this->expect_accepts_donations_meta( 11, null );
		Functions\expect( 'wp_generate_uuid4' )->once()->andReturn( '' );
		Functions\expect( 'rest_url' )
			->once()
			->with( self::DONATIONS_REST_BASE )
			->andReturn( self::DONATIONS_REST_URL );

		$renderer = $this->create_renderer();
		$markup = $renderer->render();

		self::assertMatchesRegularExpression( '/data-donation-id="[0-9a-f-]{36}"/i', $markup );
	}

	#[Test]
	public function render_uses_saved_default_amount_when_available(): void {

		Functions\expect( 'get_the_ID' )->once()->andReturn( 15 );
		Functions\expect( 'get_queried_object_id' )->never();
		$this->expect_accepts_donations_meta( 15, null );
		Functions\expect( 'wp_generate_uuid4' )->once()->andReturn( '123e4567-e89b-12d3-a456-426614174015' );
		Functions\expect( 'rest_url' )
			->once()
			->with( self::DONATIONS_REST_BASE )
			->andReturn( self::DONATIONS_REST_URL );

		$renderer = $this->create_renderer( 250, 'Amount' );
		$markup = $renderer->render();

		self::assertStringContainsString( 'value="250"', $markup );
	}

	#[Test]
	public function render_uses_saved_default_amount_label_when_available(): void {

		Functions\expect( 'get_the_ID' )->once()->andReturn( 24 );
		Functions\expect( 'get_queried_object_id' )->never();
		$this->expect_accepts_donations_meta( 24, null );
		Functions\expect( 'wp_generate_uuid4' )->once()->andReturn( '123e4567-e89b-12d3-a456-426614174024' );
		Functions\expect( 'rest_url' )
			->once()
			->with( self::DONATIONS_REST_BASE )
			->andReturn( self::DONATIONS_REST_URL );

		$renderer = $this->create_renderer( 10, 'Your amount' );
		$markup = $renderer->render();

		self::assertStringContainsString( '>Your amount</label>', $markup );
	}

	#[Test]
	public function render_renders_the_default_amount_label_as_a_visible_label(): void {

		Functions\expect( 'get_the_ID' )->once()->andReturn( 99 );
		Functions\expect( 'get_queried_object_id' )->never();
		$this->expect_accepts_donations_meta( 99, null );
		Functions\expect( 'wp_generate_uuid4' )->once()->andReturn( '123e4567-e89b-12d3-a456-426614174099' );
		Functions\expect( 'rest_url' )
			->once()
			->with( self::DONATIONS_REST_BASE )
			->andReturn( self::DONATIONS_REST_URL );

		$renderer = $this->create_renderer( 35, 'Amount' );
		$markup = $renderer->render();

		self::assertStringContainsString( '<label class="fundrik-donation-form__label"', $markup );
		self::assertStringNotContainsString( 'aria-label=', $markup );
	}
}
