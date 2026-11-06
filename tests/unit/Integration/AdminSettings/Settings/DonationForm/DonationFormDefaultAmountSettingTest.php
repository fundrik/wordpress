<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\AdminSettings\Settings\DonationForm;

use Fundrik\WordPress\Integration\AdminSettings\Settings\DonationForm\DonationFormDefaultAmountSetting;
use Fundrik\WordPress\Integration\Helpers\SettingFieldRenderer;
use Fundrik\WordPress\Integration\WpSchemaType;
use Fundrik\WordPress\Tests\WordPressTestCase;
use InvalidArgumentException;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( DonationFormDefaultAmountSetting::class )]
#[UsesClass( SettingFieldRenderer::class )]
final class DonationFormDefaultAmountSettingTest extends WordPressTestCase {

	private DonationFormDefaultAmountSetting $setting;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->setting = new DonationFormDefaultAmountSetting();
	}

	#[Test]
	public function get_id_returns_default_amount(): void {

		self::assertSame( 'default_amount', $this->setting->get_id() );
	}

	#[Test]
	public function get_label_returns_default_donation_amount_label(): void {

		self::assertSame( 'Default donation amount', $this->setting->get_label() );
	}

	#[Test]
	public function get_default_value_returns_ten(): void {

		self::assertSame( 10, $this->setting->get_default_value() );
	}

	#[Test]
	public function get_value_type_returns_integer_type(): void {

		self::assertSame( WpSchemaType::Integer, $this->setting->get_value_type() );
	}

	#[Test]
	#[DataProvider( 'valid_amount_cases' )]
	public function sanitize_value_returns_positive_integer( int|string $value, int $expected ): void {

		self::assertSame( $expected, $this->setting->sanitize_value( $value ) );
	}

	#[Test]
	#[DataProvider( 'non_positive_amount_cases' )]
	public function sanitize_value_throws_for_non_positive_integer( int $value ): void {

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage(
			sprintf( 'Default donation amount must be a positive integer. Given: %d.', $value ),
		);

		$this->setting->sanitize_value( $value );
	}

	#[Test]
	#[DataProvider( 'unsupported_value_cases' )]
	public function sanitize_value_throws_for_unsupported_value( float|string $value ): void {

		$this->expectException( InvalidArgumentException::class );

		$this->setting->sanitize_value( $value );
	}

	#[Test]
	public function render_outputs_default_amount_field(): void {

		ob_start();
		$this->setting->render(
			[
				'field_name' => 'fundrik_donation_form_default_amount',
				'input_id' => 'fundrik_donation_form_default_amount',
				'value' => 25,
			],
		);
		$output = ob_get_clean();

		self::assertSame(
			'<input type="number" name="fundrik_donation_form_default_amount" class="regular-text"'
			. ' id="fundrik_donation_form_default_amount" value="25" min="1" step="1">',
			$output,
		);
	}

	/**
	 * Returns valid default amount cases.
	 *
	 * @return iterable<string, array{int|string, int}> Valid default amount cases.
	 */
	public static function valid_amount_cases(): iterable {

		yield 'minimum integer' => [ 1, 1 ];
		yield 'decimal string' => [ '25', 25 ];
		yield 'maximum integer' => [ PHP_INT_MAX, PHP_INT_MAX ];
	}

	/**
	 * Returns non-positive default amount cases.
	 *
	 * @return iterable<string, array{int}> Non-positive default amount cases.
	 */
	public static function non_positive_amount_cases(): iterable {

		yield 'zero' => [ 0 ];
		yield 'negative integer' => [ -1 ];
	}

	/**
	 * Returns unsupported default amount cases.
	 *
	 * @return iterable<string, array{float|string}> Unsupported default amount cases.
	 */
	public static function unsupported_value_cases(): iterable {

		yield 'float' => [ 10.5 ];
		yield 'non-integer string' => [ '10.5' ];
	}
}
