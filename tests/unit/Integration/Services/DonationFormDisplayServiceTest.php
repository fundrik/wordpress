<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Services;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use DateTimeImmutable;
use DateTimeZone;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRead\CampaignReadPort;
use Fundrik\Core\Components\Campaigns\Application\ReadModels\Campaign;
use Fundrik\Core\Components\Campaigns\Application\Services\CampaignQueryService;
use Fundrik\Core\Components\Campaigns\Application\UseCases\ReadCampaignById\ReadCampaignByIdHandler;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\UtcDateTime;
use Fundrik\WordPress\Infrastructure\Ports\Storage\StoragePort;
use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsFieldRenderer;
use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsReader;
use Fundrik\WordPress\Integration\AdminSettings\Groups\DonationFormSettingsGroup;
use Fundrik\WordPress\Integration\AdminSettings\Settings\DonationForm\DonationFormDefaultAmountLabelSetting;
use Fundrik\WordPress\Integration\AdminSettings\Settings\DonationForm\DonationFormDefaultAmountSetting;
use Fundrik\WordPress\Integration\Helpers\OptionReader;
use Fundrik\WordPress\Integration\Renderers\DonationForm\DonationFormRenderData;
use Fundrik\WordPress\Integration\Renderers\DonationForm\DonationFormRenderer;
use Fundrik\WordPress\Integration\RestApi\RestRouteDefinitions;
use Fundrik\WordPress\Integration\RestApi\Routes\DonationsRestRoute;
use Fundrik\WordPress\Integration\Services\CampaignLookupService;
use Fundrik\WordPress\Integration\Services\DonationFormDisplayService;
use Fundrik\WordPress\Integration\WordPressRuntime\WordPressRuntime;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\NullLogger;

#[CoversClass( DonationFormDisplayService::class )]
final class DonationFormDisplayServiceTest extends WordPressTestCase {

	private CampaignReadPort&MockInterface $campaign_read;

	private DonationFormDisplayService $donation_form_display;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->campaign_read = Mockery::mock( CampaignReadPort::class );
		$this->donation_form_display = new DonationFormDisplayService(
			new CampaignLookupService(
				new CampaignQueryService(
					new ReadCampaignByIdHandler( $this->campaign_read ),
				),
				new WordPressRuntime(),
				new NullLogger(),
			),
			$this->create_settings_reader( 10, 'Amount' ),
			new DonationFormRenderer(),
		);
	}

	#[Test]
	public function render_returns_empty_string_when_campaign_is_missing(): void {

		Filters\expectApplied( 'fundrik_get_campaign' )->never();
		Filters\expectApplied( 'fundrik_donation_form_render_data' )->never();
		$this->campaign_read
			->shouldReceive( 'find_by_id' )
			->once()
			->with(
				Mockery::on(
					static fn ( EntityId $id ): bool => $id->get_value() === 42,
				),
			)
			->andReturn( null );

		self::assertSame( '', $this->donation_form_display->render( 42 ) );
	}

	#[Test]
	public function render_returns_rendered_markup_for_the_given_campaign(): void {

		Functions\expect( 'rest_url' )
			->once()
			->with( RestRouteDefinitions::get_route( DonationsRestRoute::class ) )
			->andReturn( 'http://example.test/wp-json/' . RestRouteDefinitions::get_route( DonationsRestRoute::class ) );

		$campaign = $this->make_campaign( 42 );
		Filters\expectApplied( 'fundrik_get_campaign' )
			->once()
			->with( $campaign, 42 )
			->andReturn( $campaign );
		Filters\expectApplied( 'fundrik_donation_form_render_data' )
			->once()
			->with(
				Mockery::on(
					static fn ( DonationFormRenderData $render_data ): bool => $render_data->campaign_id === 42
						&& $render_data->rest_url === 'http://example.test/wp-json/' . RestRouteDefinitions::get_route( DonationsRestRoute::class )
						&& $render_data->default_amount === 10
						&& $render_data->amount_label === 'Amount',
				),
				$campaign,
			)
			->andReturn(
				new DonationFormRenderData(
					campaign_id: 42,
					rest_url: 'http://example.test/wp-json/' . RestRouteDefinitions::get_route( DonationsRestRoute::class ),
					default_amount: 25,
					amount_label: 'Custom amount',
				),
			);
		$this->campaign_read
			->shouldReceive( 'find_by_id' )
			->once()
			->with(
				Mockery::on(
					static fn ( EntityId $id ): bool => $id->get_value() === 42,
				),
			)
			->andReturn( $campaign );

		$markup = $this->donation_form_display->render( 42 );

		self::assertStringContainsString( 'class="fundrik-donation-form"', $markup );
		self::assertStringContainsString(
			'data-rest-url="http://example.test/wp-json/' . RestRouteDefinitions::get_route( DonationsRestRoute::class ) . '"',
			$markup,
		);
		self::assertStringContainsString( 'data-campaign-id="42"', $markup );
		self::assertStringNotContainsString( 'data-donation-id=', $markup );
		self::assertStringContainsString( '>Custom amount</label>', $markup );
		self::assertStringContainsString( 'value="25"', $markup );
	}

	private function create_settings_reader( int $default_amount, string $default_amount_label ): AdminSettingsReader {

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

		$field_renderer = new AdminSettingsFieldRenderer();

		return new AdminSettingsReader(
			new OptionReader( $storage ),
			new DonationFormSettingsGroup(
				new DonationFormDefaultAmountSetting( $field_renderer ),
				new DonationFormDefaultAmountLabelSetting( $field_renderer ),
			),
		);
	}

	private function make_campaign( int $id ): Campaign {

		return new Campaign(
			id: $id,
			title: 'Campaign',
			accepts_donations: true,
			currency_code: 'RUB',
			target_amount: 1_000,
			created_at: UtcDateTime::create(
				new DateTimeImmutable( '2026-03-21 10:00:00', new DateTimeZone( 'UTC' ) ),
			),
			updated_at: null,
		);
	}
}
