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
use Fundrik\WordPress\Integration\ReadModels\Campaign as WpCampaign;
use Fundrik\WordPress\Components\Campaigns\Domain\CampaignStatusPolicy;
use Fundrik\WordPress\Presentation\Renderers\CampaignSummary\CampaignSummaryRenderer;
use Fundrik\WordPress\Presentation\Formatters\MoneyFormatter;
use Fundrik\WordPress\Integration\Services\CampaignLookupService;
use Fundrik\WordPress\Integration\Services\CampaignSummaryDisplayService;
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

#[CoversFunction( 'fundrik_get_campaign_summary' )]
#[CoversFunction( 'fundrik_the_campaign_summary' )]
final class CampaignSummaryFunctionsTest extends WordPressTestCase {

	private CampaignReadPort&MockInterface $campaign_read;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		require_once dirname( __DIR__, 4 ) . '/src/php/Integration/Functions/CampaignFunctions.php';
		require_once dirname( __DIR__, 4 ) . '/src/php/Integration/Functions/CampaignSummaryFunctions.php';

		Functions\when( 'number_format_i18n' )->alias(
			static fn ( $number, int $decimals ): string => number_format( (float) $number, $decimals, '.', ',' ),
		);
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
	public function fundrik_get_campaign_summary_throws_when_runtime_container_is_unavailable(): void {

		$this->expectException( LogicException::class );

		fundrik_get_campaign_summary( 42 );
	}

	#[Test]
	public function fundrik_get_campaign_summary_returns_empty_string_when_campaign_is_missing(): void {

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
		$container->instance( CampaignSummaryDisplayService::class, $this->create_campaign_summary_display_service() );
		RuntimeContainer::set( $container );

		self::assertSame( '', fundrik_get_campaign_summary( 42 ) );
	}

	#[Test]
	public function fundrik_get_campaign_summary_returns_rendered_markup_for_the_given_campaign(): void {

		$campaign = $this->make_campaign( 42, true, 1_000, 2_500, 3 );
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
		$container->instance( CampaignSummaryDisplayService::class, $this->create_campaign_summary_display_service() );
		RuntimeContainer::set( $container );

		$markup = fundrik_get_campaign_summary( 42 );

		self::assertStringContainsString( 'class="fundrik-campaign-summary"', $markup );
		self::assertStringContainsString( 'data-campaign-id="42"', $markup );
		self::assertStringContainsString( 'Goal reached', $markup );
		self::assertStringContainsString( '25.00 RUB', $markup );
	}

	#[Test]
	public function fundrik_get_campaign_summary_accepts_display_options(): void {

		$campaign = $this->make_campaign( 42, true, 1_000, 2_500, 3 );
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
		$container->instance( CampaignSummaryDisplayService::class, $this->create_campaign_summary_display_service() );
		RuntimeContainer::set( $container );

		$markup = fundrik_get_campaign_summary(
			42,
			[
				'showStatus' => false,
				'showGoal' => false,
				'showCollectedAmount' => false,
				'showDonationsCount' => false,
			],
		);

		self::assertStringNotContainsString( 'fundrik-campaign-summary__status', $markup );
		self::assertStringNotContainsString( 'data-metric="collected"', $markup );
	}

	#[Test]
	public function fundrik_the_campaign_summary_echoes_the_rendered_markup(): void {

		$campaign = $this->make_campaign( 42, false, 1_000, 500, 2 );
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
		$container->instance( CampaignSummaryDisplayService::class, $this->create_campaign_summary_display_service() );
		RuntimeContainer::set( $container );

		$this->expectOutputRegex( '/data-status="donations_disabled"/' );

		fundrik_the_campaign_summary( 42 );
	}

	private function create_campaign_summary_display_service(): CampaignSummaryDisplayService {

		return new CampaignSummaryDisplayService(
			new CampaignLookupService(
				new CampaignQueryService(
					new ReadCampaignByIdHandler(
						$this->campaign_read,
					),
				),
				new WordPressRuntime(),
				new NullLogger(),
			),
			new CampaignStatusPolicy(),
			new CampaignSummaryRenderer( new MoneyFormatter() ),
		);
	}

	private function make_campaign(
		int $id,
		bool $accepts_donations = true,
		?int $target_amount = 1_000,
		int $collected_amount = 0,
		int $donations_count = 0,
	): Campaign {

		return new Campaign(
			id: $id,
			title: 'Campaign',
			accepts_donations: $accepts_donations,
			currency_code: 'RUB',
			target_amount: $target_amount,
			collected_amount: $collected_amount,
			donations_count: $donations_count,
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
