<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Functions;

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
use Fundrik\WordPress\Presentation\Renderers\DonationForm\DonationFormRenderer;
use Fundrik\WordPress\Integration\RestApi\RestRouteDefinitions;
use Fundrik\WordPress\Integration\RestApi\Routes\DonationsRestRoute;
use Fundrik\WordPress\Integration\ReadModels\Campaign as WpCampaign;
use Fundrik\WordPress\Integration\Services\CampaignLookupService;
use Fundrik\WordPress\Integration\Services\DonationFormDisplayService;
use Fundrik\WordPress\Integration\WordPressRuntime\WordPressRuntime;
use Fundrik\WordPress\Kernel\Container\RuntimeContainer;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Illuminate\Container\Container;
use LogicException;
use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\NullLogger;

#[CoversFunction( 'fundrik_get_donation_form' )]
#[CoversFunction( 'fundrik_the_donation_form' )]
final class DonationFormFunctionsTest extends WordPressTestCase {

	private CampaignReadPort&MockInterface $campaign_read;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		require_once dirname( __DIR__, 4 ) . '/src/php/Integration/Functions/CampaignFunctions.php';
		require_once dirname( __DIR__, 4 ) . '/src/php/Integration/Functions/DonationFormFunctions.php';

		Functions\when( 'get_post_field' )->alias(
			static fn ( string $field, int $post_id, string $context = 'raw' ): ?string =>
				$field === 'post_name' ? 'campaign-' . $post_id : null,
		);
		Functions\when( 'get_permalink' )->alias(
			static fn ( int $post_id ): string => 'https://example.test/campaign-' . $post_id . '/',
		);
		Functions\when( 'get_post_thumbnail_id' )->alias(
			static fn ( int $post_id ): int => $post_id === 42 ? 99 : 0,
		);
		Functions\when( 'wp_get_attachment_image_url' )->alias(
			static fn ( int $attachment_id, string $size = 'full' ): string => 'https://example.test/media/' . $attachment_id . '.jpg',
		);

		RuntimeContainer::reset();
		$this->campaign_read = Mockery::mock( CampaignReadPort::class );
	}

	#[Override]
	protected function tearDown(): void {

		RuntimeContainer::reset();

		parent::tearDown();
	}

	#[Test]
	public function fundrik_get_donation_form_throws_when_runtime_container_is_unavailable(): void {

		$this->expectException( LogicException::class );

		fundrik_get_donation_form( 42 );
	}

	#[Test]
	public function fundrik_get_donation_form_returns_empty_string_when_campaign_is_missing(): void {

		Filters\expectApplied( 'fundrik_get_campaign' )->never();
		$this->campaign_read
			->shouldReceive( 'find_by_id' )
			->once()
			->with(
				Mockery::on(
					static fn ( EntityId $id ): bool => $id->get_value() === 42,
				),
			)
			->andReturn( null );

		$container = new Container();
		$container->instance( DonationFormDisplayService::class, $this->create_donation_form_display_service() );
		RuntimeContainer::set( $container );

		self::assertSame( '', fundrik_get_donation_form( 42 ) );
	}

	#[Test]
	public function fundrik_get_donation_form_returns_rendered_markup_for_the_given_campaign(): void {

		Functions\expect( 'rest_url' )
			->once()
			->with( RestRouteDefinitions::get_route( DonationsRestRoute::class ) )
			->andReturn( 'http://example.test/wp-json/' . RestRouteDefinitions::get_route( DonationsRestRoute::class ) );

		$campaign = $this->make_campaign( 42 );
		Filters\expectApplied( 'fundrik_get_campaign' )
			->once()
			->with(
				Mockery::on(
					static fn ( WpCampaign $campaign_view ): bool => $campaign_view->get_id() === 42
						&& $campaign_view->get_title() === 'Campaign'
						&& $campaign_view->get_permalink() === 'https://example.test/campaign-42/'
						&& $campaign_view->get_featured_image_id() === 99,
				),
				42,
			)
			->andReturn( $this->make_campaign_view( $campaign ) );
		$this->campaign_read
			->shouldReceive( 'find_by_id' )
			->once()
			->with(
				Mockery::on(
					static fn ( EntityId $id ): bool => $id->get_value() === 42,
				),
			)
			->andReturn( $campaign );

		$container = new Container();
		$container->instance( DonationFormDisplayService::class, $this->create_donation_form_display_service() );
		RuntimeContainer::set( $container );

		$markup = fundrik_get_donation_form( 42 );

		self::assertStringContainsString( 'class="fundrik-donation-form"', $markup );
		self::assertStringContainsString(
			'data-rest-url="http://example.test/wp-json/' . RestRouteDefinitions::get_route( DonationsRestRoute::class ) . '"',
			$markup,
		);
		self::assertStringContainsString( 'data-campaign-id="42"', $markup );
		self::assertStringNotContainsString( 'data-donation-id=', $markup );
		self::assertStringContainsString( '>Amount</label>', $markup );
	}

	#[Test]
	public function fundrik_the_donation_form_echoes_the_rendered_markup(): void {

		Functions\expect( 'rest_url' )
			->once()
			->with( RestRouteDefinitions::get_route( DonationsRestRoute::class ) )
			->andReturn( 'http://example.test/wp-json/' . RestRouteDefinitions::get_route( DonationsRestRoute::class ) );

		$campaign = $this->make_campaign( 42 );
		Filters\expectApplied( 'fundrik_get_campaign' )
			->once()
			->with(
				Mockery::on(
					static fn ( WpCampaign $campaign_view ): bool => $campaign_view->get_id() === 42
						&& $campaign_view->get_title() === 'Campaign',
				),
				42,
			)
			->andReturn( $this->make_campaign_view( $campaign ) );
		$this->campaign_read
			->shouldReceive( 'find_by_id' )
			->once()
			->with(
				Mockery::on(
					static fn ( EntityId $id ): bool => $id->get_value() === 42,
				),
			)
			->andReturn( $campaign );

		$container = new Container();
		$container->instance( DonationFormDisplayService::class, $this->create_donation_form_display_service() );
		RuntimeContainer::set( $container );

		$this->expectOutputRegex( '/data-campaign-id="42"/' );

		fundrik_the_donation_form( 42 );
	}

	private function create_donation_form_display_service(): DonationFormDisplayService {

		return new DonationFormDisplayService(
			new CampaignLookupService(
				new CampaignQueryService(
					new ReadCampaignByIdHandler(
						$this->campaign_read,
					),
				),
				new WordPressRuntime(),
				new NullLogger(),
			),
			$this->create_settings_reader( 10, 'Amount' ),
			new DonationFormRenderer(),
		);
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

	private function make_campaign( int $id, bool $accepts_donations = true ): Campaign {

		return new Campaign(
			id: $id,
			title: 'Campaign',
			accepts_donations: $accepts_donations,
			currency_code: 'RUB',
			target_amount: 1_000,
			collected_amount: 0,
			donations_count: 0,
			created_at: UtcDateTime::create(
				new DateTimeImmutable( '2026-03-21 10:00:00', new DateTimeZone( 'UTC' ) ),
			),
			updated_at: null,
		);
	}

	private function make_campaign_view( Campaign $campaign ): WpCampaign {

		return new WpCampaign(
			id: $campaign->get_id(),
			title: $campaign->get_title(),
			accepts_donations: $campaign->accepts_donations(),
			currency_code: $campaign->get_currency_code(),
			target_amount: $campaign->get_target_amount(),
			collected_amount: $campaign->get_collected_amount(),
			donations_count: $campaign->get_donations_count(),
			created_at: $campaign->get_created_at(),
			updated_at: $campaign->get_updated_at(),
			permalink: 'https://example.test/campaign-' . $campaign->get_id() . '/',
			featured_image_id: $campaign->get_id() === 42 ? 99 : null,
		);
	}
}
