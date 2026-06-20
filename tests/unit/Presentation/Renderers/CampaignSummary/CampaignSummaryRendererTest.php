<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Presentation\Renderers\CampaignSummary;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use Fundrik\WordPress\Presentation\Renderers\CampaignSummary\CampaignSummaryRenderData;
use Fundrik\WordPress\Presentation\Renderers\CampaignSummary\CampaignSummaryRenderer;
use Fundrik\WordPress\Presentation\Formatters\MoneyFormatter;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( CampaignSummaryRenderer::class )]
final class CampaignSummaryRendererTest extends WordPressTestCase {

	private CampaignSummaryRenderer $renderer;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		Functions\when( 'number_format_i18n' )->alias(
			static fn ( $number, int $decimals ): string => number_format( (float) $number, $decimals, '.', ',' ),
		);
		$this->renderer = new CampaignSummaryRenderer( new MoneyFormatter() );
	}

	#[Test]
	public function render_applies_markup_filters(): void {

		$data = new CampaignSummaryRenderData(
			campaign_id: 42,
			campaign_status: 'target_reached',
			currency_code: 'RUB',
			collected_amount: 2_500,
			target_amount: 2_000,
			donations_count: 5,
			show_status: true,
			show_goal: true,
			show_collected_amount: true,
			show_donations_count: true,
		);
		$filtered_parts = [
			'wrapper_open' => '<section class="fundrik-campaign-summary" data-test="value">',
			'status' => '<p class="fundrik-campaign-summary__status" data-status="target_reached">Goal reached</p>',
			'metrics' => '<div class="fundrik-campaign-summary__metrics"><div class="fundrik-campaign-summary__metric">Metrics</div></div>',
			'wrapper_close' => '</section>',
		];
		$filtered_markup = implode( '', $filtered_parts );

		Filters\expectApplied( 'fundrik_campaign_summary_markup_parts' )
			->once()
			->with(
				Mockery::on(
					static fn ( array $parts ): bool => isset(
						$parts['wrapper_open'],
						$parts['status'],
						$parts['metrics'],
						$parts['wrapper_close'],
					),
				),
				$data,
			)
			->andReturn( $filtered_parts );
		Filters\expectApplied( 'fundrik_campaign_summary_markup' )
			->once()
			->with( $filtered_markup, $data, $filtered_parts )
			->andReturn( '<div class="custom-wrapper">' . $filtered_markup . '</div>' );

		$markup = $this->renderer->render( $data );

		self::assertStringContainsString( 'custom-wrapper', $markup );
		self::assertStringContainsString( 'Goal reached', $markup );
	}
}
