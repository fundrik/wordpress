<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Renderers\DonationForm;

use Brain\Monkey\Filters;
use Fundrik\WordPress\Integration\Renderers\DonationForm\DonationFormRenderData;
use Fundrik\WordPress\Integration\Renderers\DonationForm\DonationFormRenderer;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( DonationFormRenderer::class )]
final class DonationFormRendererTest extends WordPressTestCase {

	private DonationFormRenderer $renderer;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->renderer = new DonationFormRenderer();
	}

	#[Test]
	public function render_applies_markup_filters(): void {

		$data = new DonationFormRenderData(
			campaign_id: 42,
			rest_url: 'http://example.test/wp-json/fundrik/v1/donations',
			default_amount: 10,
			amount_label: 'Amount',
		);
		$filtered_parts = [
			'form_open' => '<form class="fundrik-donation-form" data-test="value">',
			'amount_field' => '<div class="fundrik-donation-form__amount-field"><label class="fundrik-donation-form__amount-label" for="fundrik-donation-amount-42">Amount</label><input class="fundrik-donation-form__amount-input" value="10" /></div>',
			'notice' => '<p class="fundrik-donation-form__notice">Custom notice</p>',
			'submit_button' => '<button class="fundrik-donation-form__submit" type="submit">Donate</button>',
			'message' => '<p class="fundrik-donation-form__message" aria-live="polite"></p>',
			'form_close' => '</form>',
		];
		$filtered_markup = implode( '', $filtered_parts );

		Filters\expectApplied( 'fundrik_donation_form_markup_parts' )
			->once()
			->with(
				Mockery::on(
					static fn ( array $parts ): bool => isset(
						$parts['form_open'],
						$parts['amount_field'],
						$parts['submit_button'],
						$parts['message'],
						$parts['form_close'],
					),
				),
				$data,
			)
			->andReturn( $filtered_parts );
		Filters\expectApplied( 'fundrik_donation_form_markup' )
			->once()
			->with( $filtered_markup, $data, $filtered_parts )
			->andReturn( '<div class="custom-wrapper">' . $filtered_markup . '</div>' );

		$markup = $this->renderer->render( $data );

		self::assertStringContainsString( 'custom-wrapper', $markup );
		self::assertStringContainsString( 'fundrik-donation-form__notice', $markup );
		self::assertStringNotContainsString( 'data-donation-id=', $markup );
	}
}
