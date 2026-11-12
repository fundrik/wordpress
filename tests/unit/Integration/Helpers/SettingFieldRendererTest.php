<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Helpers;

use Brain\Monkey\Functions;
use Fundrik\WordPress\Integration\Helpers\SettingFieldRenderer;
use Fundrik\WordPress\Tests\WordPressTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( SettingFieldRenderer::class )]
final class SettingFieldRendererTest extends WordPressTestCase {

	#[Test]
	public function render_number_field_outputs_number_input_without_optional_attributes(): void {

		ob_start();
		SettingFieldRenderer::render_number_field( 'donation_amount', 'donation_amount', 25 );
		$output = ob_get_clean();

		self::assertSame(
			'<input type="number" name="donation_amount" class="regular-text" id="donation_amount" value="25">',
			$output,
		);
	}

	#[Test]
	public function render_number_field_outputs_configured_optional_attributes(): void {

		ob_start();
		SettingFieldRenderer::render_number_field(
			'donation_amount',
			'donation_amount',
			25,
			min: 1,
			max: 10_000,
			step: 5,
		);
		$output = ob_get_clean();

		self::assertSame(
			'<input type="number" name="donation_amount" class="regular-text" id="donation_amount"'
			. ' value="25" min="1" max="10000" step="5">',
			$output,
		);
	}

	#[Test]
	public function render_text_field_outputs_escaped_text_input_without_maxlength(): void {

		ob_start();
		SettingFieldRenderer::render_text_field( 'setting_name', 'setting_id', 'A "quoted" value' );
		$output = ob_get_clean();

		self::assertSame(
			'<input type="text" name="setting_name" class="regular-text" id="setting_id"'
			. ' value="A &quot;quoted&quot; value">',
			$output,
		);
	}

	#[Test]
	public function render_text_field_outputs_configured_maxlength(): void {

		ob_start();
		SettingFieldRenderer::render_text_field( 'currency', 'currency', 'RUB', maxlength: 3 );
		$output = ob_get_clean();

		self::assertSame(
			'<input type="text" name="currency" class="regular-text" id="currency" value="RUB" maxlength="3">',
			$output,
		);
	}

	#[Test]
	#[DataProvider( 'checkbox_cases' )]
	public function render_checkbox_field_outputs_checkbox( bool $checked, string $checked_attribute, ): void {

		Functions\expect( 'checked' )
			->once()
			->with( $checked, true, false )
			->andReturn( $checked_attribute );

		ob_start();
		SettingFieldRenderer::render_checkbox_field( 'enabled', 'enabled', $checked, 'Enable <gateway>' );
		$output = ob_get_clean();

		self::assertSame(
			'<input type="hidden" name="enabled" value="0">'
			. '<label for="enabled">'
			. '<input type="checkbox" name="enabled" id="enabled" value="1"'
			. $checked_attribute
			. '> Enable &lt;gateway&gt;</label>',
			$output,
		);
	}

	#[Test]
	public function render_select_field_outputs_options_and_selected_value(): void {

		Functions\expect( 'selected' )
			->once()
			->with( 'second', 'first', false )
			->andReturn( '' );

		Functions\expect( 'selected' )
			->once()
			->with( 'second', 'second', false )
			->andReturn( ' selected="selected"' );

		ob_start();
		SettingFieldRenderer::render_select_field(
			'gateway',
			'gateway',
			'second',
			[
				'first' => 'First <Gateway>',
				'second' => 'Second & Gateway',
			],
		);
		$output = ob_get_clean();

		self::assertSame(
			'<select name="gateway" id="gateway" class="regular-text">'
			. '<option value="first">First &lt;Gateway&gt;</option>'
			. '<option value="second" selected="selected">Second &amp; Gateway</option>'
			. '</select>',
			$output,
		);
	}

	#[Test]
	public function render_select_field_outputs_empty_select_when_options_are_empty(): void {

		ob_start();
		SettingFieldRenderer::render_select_field( 'gateway', 'gateway', '', [] );
		$output = ob_get_clean();

		self::assertSame( '<select name="gateway" id="gateway" class="regular-text"></select>', $output );
	}

	/**
	 * Returns checkbox rendering cases.
	 *
	 * @return iterable<string, array{bool, string}> Checkbox rendering cases.
	 */
	public static function checkbox_cases(): iterable {

		yield 'checked' => [ true, ' checked="checked"' ];
		yield 'unchecked' => [ false, '' ];
	}
}
