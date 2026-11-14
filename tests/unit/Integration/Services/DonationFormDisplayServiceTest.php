<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Services;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRead\CampaignReadPort;
use Fundrik\Core\Components\Campaigns\Application\ReadModels\Campaign as CoreCampaign;
use Fundrik\Core\Components\Campaigns\Application\Services\CampaignQueryService;
use Fundrik\Core\Components\Campaigns\Application\UseCases\ReadCampaignById\ReadCampaignByIdHandler;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\WordPress\Components\Campaigns\Domain\CampaignId;
use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsReader;
use Fundrik\WordPress\Integration\AdminSettings\Groups\CheckoutSettingsGroup;
use Fundrik\WordPress\Integration\AdminSettings\Groups\DonationFormSettingsGroup;
use Fundrik\WordPress\Integration\AdminSettings\Settings\Checkout\CheckoutCancelUrlSetting;
use Fundrik\WordPress\Integration\AdminSettings\Settings\Checkout\CheckoutSuccessUrlSetting;
use Fundrik\WordPress\Integration\AdminSettings\Settings\DonationForm\DonationFormDefaultAmountLabelSetting;
use Fundrik\WordPress\Integration\AdminSettings\Settings\DonationForm\DonationFormDefaultAmountSetting;
use Fundrik\WordPress\Integration\AdminSettings\Settings\General\SelectedGatewaySetting;
use Fundrik\WordPress\Integration\Gateways\GatewayInterface;
use Fundrik\WordPress\Integration\Gateways\GatewayResolutionException;
use Fundrik\WordPress\Integration\Gateways\GatewayResolver;
use Fundrik\WordPress\Integration\Helpers\OptionReader;
use Fundrik\WordPress\Integration\Helpers\SettingOptionName;
use Fundrik\WordPress\Integration\ReadModels\Campaign;
use Fundrik\WordPress\Integration\RestApi\RestRouteDefinitions;
use Fundrik\WordPress\Integration\RestApi\Routes\DonationCheckoutRestRoute;
use Fundrik\WordPress\Integration\Services\CampaignLookupService;
use Fundrik\WordPress\Integration\Services\DonationFormDisplayService;
use Fundrik\WordPress\Integration\WordPressRuntime\WordPressRuntimeInterface;
use Fundrik\WordPress\Presentation\Renderers\DonationForm\DonationFormRenderData;
use Fundrik\WordPress\Presentation\Renderers\DonationForm\DonationFormRenderer;
use Fundrik\WordPress\Tests\Fixtures\FakeStorage;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use Psr\Log\NullLogger;

#[CoversClass( DonationFormDisplayService::class )]
#[UsesClass( CampaignId::class )]
#[UsesClass( AdminSettingsReader::class )]
#[UsesClass( CheckoutSettingsGroup::class )]
#[UsesClass( DonationFormSettingsGroup::class )]
#[UsesClass( CheckoutCancelUrlSetting::class )]
#[UsesClass( CheckoutSuccessUrlSetting::class )]
#[UsesClass( DonationFormDefaultAmountLabelSetting::class )]
#[UsesClass( DonationFormDefaultAmountSetting::class )]
#[UsesClass( SelectedGatewaySetting::class )]
#[UsesClass( GatewayResolutionException::class )]
#[UsesClass( GatewayResolver::class )]
#[UsesClass( OptionReader::class )]
#[UsesClass( SettingOptionName::class )]
#[UsesClass( Campaign::class )]
#[UsesClass( RestRouteDefinitions::class )]
#[UsesClass( DonationCheckoutRestRoute::class )]
#[UsesClass( CampaignLookupService::class )]
#[UsesClass( DonationFormRenderData::class )]
#[UsesClass( DonationFormRenderer::class )]
final class DonationFormDisplayServiceTest extends WordPressTestCase {

	private const int CAMPAIGN_ID = 42;

	private const string GATEWAY_ID = 'example';

	private const string CHECKOUT_URL = 'https://example.test/wp-json/fundrik/v1/checkout';

	private CampaignReadPort&MockInterface $campaign_read;
	private FakeStorage $storage;
	private DonationFormDisplayService $service;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		Functions\when( 'get_permalink' )->justReturn( 'https://example.test/campaigns/help-the-shelter/' );
		Functions\when( 'get_post_thumbnail_id' )->justReturn( 91 );

		$this->campaign_read = Mockery::mock( CampaignReadPort::class );
		$this->storage = new FakeStorage(
			[
				'fundrik_donation_form_default_amount_setting' => 25,
				'fundrik_donation_form_default_amount_label_setting' => 'Contribution',
				'fundrik_checkout_selected_gateway_setting' => self::GATEWAY_ID,
				'fundrik_example_enabled_setting' => true,
			],
		);

		$gateway = Mockery::mock( GatewayInterface::class );
		$gateway->shouldReceive( 'get_id' )->andReturn( self::GATEWAY_ID );
		$option_reader = new OptionReader( $this->storage );
		$settings_reader = new AdminSettingsReader(
			$option_reader,
			new DonationFormSettingsGroup(
				new DonationFormDefaultAmountSetting(),
				new DonationFormDefaultAmountLabelSetting(),
			),
			new CheckoutSettingsGroup(
				new SelectedGatewaySetting( $gateway ),
				new CheckoutSuccessUrlSetting(),
				new CheckoutCancelUrlSetting(),
			),
		);

		$this->service = new DonationFormDisplayService(
			new CampaignLookupService(
				new CampaignQueryService( new ReadCampaignByIdHandler( $this->campaign_read ) ),
				Mockery::mock( WordPressRuntimeInterface::class ),
				new NullLogger(),
			),
			$settings_reader,
			new GatewayResolver(
				$settings_reader,
				$option_reader,
				$gateway,
			),
			new DonationFormRenderer(),
		);
	}

	#[Test]
	public function render_returns_empty_string_when_campaign_is_missing(): void {

		$this->expect_campaign_read( null );

		self::assertSame( '', $this->service->render( self::CAMPAIGN_ID ) );
	}

	#[Test]
	public function render_returns_empty_string_when_campaign_does_not_accept_donations(): void {

		$this->expect_campaign_read(
			$this->make_core_campaign_read_model( accepts_donations: false ),
		);

		self::assertSame( '', $this->service->render( self::CAMPAIGN_ID ) );
	}

	#[Test]
	public function render_returns_empty_string_when_active_gateway_is_unavailable(): void {

		$this->expect_campaign_read( $this->make_core_campaign_read_model() );
		$this->storage->set( 'fundrik_example_enabled_setting', false );

		self::assertSame( '', $this->service->render( self::CAMPAIGN_ID ) );
	}

	#[Test]
	public function render_uses_filtered_render_data_for_campaign(): void {

		$this->expect_campaign_read( $this->make_core_campaign_read_model() );
		$this->expect_checkout_url();

		$filtered_render_data = new DonationFormRenderData(
			campaign_id: 77,
			checkout_url: 'https://example.test/custom-checkout/',
			default_amount: 50,
			amount_label: 'Custom amount',
		);

		Filters\expectApplied( 'fundrik_donation_form_render_data' )
			->once()
			->with(
				Mockery::on(
					static fn ( DonationFormRenderData $data ): bool => self::matches_default_render_data( $data ),
				),
				Mockery::on(
					static fn ( Campaign $campaign ): bool => $campaign->get_id() === self::CAMPAIGN_ID,
				),
			)
			->andReturn( $filtered_render_data );

		$markup = $this->service->render( self::CAMPAIGN_ID );

		self::assertStringContainsString( 'data-checkout-url="https://example.test/custom-checkout/"', $markup );
		self::assertStringContainsString( 'data-campaign-id="77"', $markup );
		self::assertStringContainsString( 'value="50"', $markup );
		self::assertStringContainsString( '>Custom amount</label>', $markup );
	}

	#[Test]
	public function render_uses_original_render_data_when_filter_result_is_invalid(): void {

		$this->expect_campaign_read( $this->make_core_campaign_read_model() );
		$this->expect_checkout_url();

		Filters\expectApplied( 'fundrik_donation_form_render_data' )
			->once()
			->with( Mockery::type( DonationFormRenderData::class ), Mockery::type( Campaign::class ) )
			->andReturn( 'invalid' );

		Functions\expect( '_doing_it_wrong' )
			->once()
			->with(
				'fundrik_donation_form_render_data',
				'Filter must return donation form render data. Given: string.',
				'1.0.0',
			);

		$markup = $this->service->render( self::CAMPAIGN_ID );

		self::assertStringContainsString( 'data-checkout-url="' . self::CHECKOUT_URL . '"', $markup );
		self::assertStringContainsString( 'data-campaign-id="42"', $markup );
		self::assertStringContainsString( 'value="25"', $markup );
		self::assertStringContainsString( '>Contribution</label>', $markup );
	}

	private function expect_campaign_read( ?CoreCampaign $campaign ): void {

		$this->campaign_read
			->shouldReceive( 'find_by_id' )
			->once()
			->with(
				Mockery::on(
					static fn ( EntityId $id ): bool => $id->get_value() === self::CAMPAIGN_ID,
				),
			)
			->andReturn( $campaign );
	}

	private function expect_checkout_url(): void {

		Functions\expect( 'rest_url' )
			->once()
			->with( 'fundrik/v1/checkout' )
			->andReturn( self::CHECKOUT_URL );
	}

	private static function matches_default_render_data( DonationFormRenderData $data ): bool {

		return $data->campaign_id === self::CAMPAIGN_ID
			&& $data->checkout_url === self::CHECKOUT_URL
			&& $data->default_amount === 25
			&& $data->amount_label === 'Contribution';
	}
}
