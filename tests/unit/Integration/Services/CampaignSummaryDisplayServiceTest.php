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
use Fundrik\WordPress\Components\Campaigns\Domain\CampaignStatusPolicy;
use Fundrik\WordPress\Integration\ReadModels\Campaign;
use Fundrik\WordPress\Integration\Services\CampaignLookupService;
use Fundrik\WordPress\Integration\Services\CampaignSummaryDisplayService;
use Fundrik\WordPress\Integration\WordPressRuntime\WordPressRuntimeInterface;
use Fundrik\WordPress\Presentation\Formatters\MoneyFormatter;
use Fundrik\WordPress\Presentation\Renderers\CampaignSummary\CampaignSummaryRenderData;
use Fundrik\WordPress\Presentation\Renderers\CampaignSummary\CampaignSummaryRenderer;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use Psr\Log\NullLogger;

#[CoversClass( CampaignSummaryDisplayService::class )]
#[UsesClass( CampaignId::class )]
#[UsesClass( CampaignStatusPolicy::class )]
#[UsesClass( Campaign::class )]
#[UsesClass( CampaignLookupService::class )]
#[UsesClass( MoneyFormatter::class )]
#[UsesClass( CampaignSummaryRenderData::class )]
#[UsesClass( CampaignSummaryRenderer::class )]
final class CampaignSummaryDisplayServiceTest extends WordPressTestCase {

	private const int CAMPAIGN_ID = 42;

	private CampaignReadPort&MockInterface $campaign_read;
	private CampaignSummaryDisplayService $service;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		Functions\when( 'get_permalink' )->justReturn( 'https://example.test/campaigns/help-the-shelter/' );
		Functions\when( 'get_post_thumbnail_id' )->justReturn( 91 );
		$this->stub_number_format_i18n();

		$this->campaign_read = Mockery::mock( CampaignReadPort::class );
		$this->service = new CampaignSummaryDisplayService(
			new CampaignLookupService(
				new CampaignQueryService( new ReadCampaignByIdHandler( $this->campaign_read ) ),
				Mockery::mock( WordPressRuntimeInterface::class ),
				new NullLogger(),
			),
			new CampaignStatusPolicy(),
			new CampaignSummaryRenderer( new MoneyFormatter() ),
		);
	}

	#[Test]
	public function render_returns_empty_string_when_campaign_is_missing(): void {

		$this->expect_campaign_read( null );

		self::assertSame( '', $this->service->render( self::CAMPAIGN_ID ) );
	}

	#[Test]
	public function render_uses_filtered_render_data_for_campaign(): void {

		$this->expect_campaign_read(
			$this->make_core_campaign_read_model(
				target_amount: 2_000,
				collected_amount: 2_500,
				donations_count: 5,
			),
		);
		$filtered_render_data = new CampaignSummaryRenderData(
			campaign_id: 77,
			campaign_status: 'donations_disabled',
			currency_code: 'USD',
			collected_amount: 0,
			target_amount: null,
			donations_count: 0,
			show_status: false,
			show_goal: false,
			show_collected_amount: false,
			show_donations_count: false,
		);

		Filters\expectApplied( 'fundrik_campaign_summary_render_data' )
			->once()
			->with(
				Mockery::on( self::matches_default_render_data( ... ) ),
				Mockery::on(
					static fn ( Campaign $campaign ): bool => $campaign->get_id() === self::CAMPAIGN_ID,
				),
			)
			->andReturn( $filtered_render_data );

		$markup = $this->service->render( self::CAMPAIGN_ID );

		self::assertStringContainsString( 'data-campaign-id="77"', $markup );
		self::assertStringContainsString( 'data-status="donations_disabled"', $markup );
	}

	#[Test]
	public function render_applies_display_options_to_render_data(): void {

		$this->expect_campaign_read( $this->make_core_campaign_read_model() );
		Filters\expectApplied( 'fundrik_campaign_summary_render_data' )
			->once()
			->with(
				Mockery::on(
					static fn ( CampaignSummaryRenderData $data ): bool => $data->show_status === false
						&& $data->show_goal === false
						&& $data->show_collected_amount === false
						&& $data->show_donations_count === false,
				),
				Mockery::type( Campaign::class ),
			);

		$markup = $this->service->render(
			self::CAMPAIGN_ID,
			[
				'showStatus' => false,
				'showGoal' => false,
				'showCollectedAmount' => false,
				'showDonationsCount' => false,
			],
		);

		self::assertNotSame( '', $markup );
	}

	#[Test]
	public function render_uses_original_render_data_when_filter_result_is_invalid(): void {

		$this->expect_campaign_read( $this->make_core_campaign_read_model() );
		Filters\expectApplied( 'fundrik_campaign_summary_render_data' )
			->once()
			->with( Mockery::type( CampaignSummaryRenderData::class ), Mockery::type( Campaign::class ) )
			->andReturn( 'invalid' );

		Functions\expect( '_doing_it_wrong' )
			->once()
			->with(
				'fundrik_campaign_summary_render_data',
				'Filter must return campaign summary render data. Given: string.',
				'1.0.0',
			);

		$markup = $this->service->render( self::CAMPAIGN_ID );

		self::assertStringContainsString( 'data-campaign-id="42"', $markup );
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

	private static function matches_default_render_data( CampaignSummaryRenderData $data ): bool {

		return $data->campaign_id === self::CAMPAIGN_ID
			&& $data->campaign_status === 'target_reached'
			&& $data->currency_code === 'RUB'
			&& $data->collected_amount === 2_500
			&& $data->target_amount === 2_000
			&& $data->donations_count === 5
			&& $data->show_status === true
			&& $data->show_goal === true
			&& $data->show_collected_amount === true
			&& $data->show_donations_count === true;
	}
}
