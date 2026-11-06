<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\AdminSettings\Settings\General;

use Fundrik\WordPress\Integration\AdminSettings\Settings\General\CurrencySetting;
use Fundrik\WordPress\Integration\Helpers\SettingFieldRenderer;
use Fundrik\WordPress\Integration\WpSchemaType;
use Fundrik\WordPress\Tests\WordPressTestCase;
use InvalidArgumentException;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( CurrencySetting::class )]
#[UsesClass( SettingFieldRenderer::class )]
final class CurrencySettingTest extends WordPressTestCase {

	private CurrencySetting $setting;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->setting = new CurrencySetting();
	}

	#[Test]
	public function get_id_returns_currency(): void {

		self::assertSame( 'currency', $this->setting->get_id() );
	}

	#[Test]
	public function get_label_returns_currency_label(): void {

		self::assertSame( 'Currency', $this->setting->get_label() );
	}

	#[Test]
	public function get_default_value_returns_rub(): void {

		self::assertSame( 'RUB', $this->setting->get_default_value() );
	}

	#[Test]
	public function get_value_type_returns_string_type(): void {

		self::assertSame( WpSchemaType::String, $this->setting->get_value_type() );
	}

	#[Test]
	public function sanitize_value_normalizes_valid_currency_code(): void {

		self::assertSame( 'USD', $this->setting->sanitize_value( ' usd ' ) );
	}

	#[Test]
	#[DataProvider( 'invalid_currency_code_cases' )]
	public function sanitize_value_throws_for_invalid_currency_code( string $value ): void {

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage(
			sprintf( 'Currency must be a 3-letter ISO 4217 code. Given: %s.', $value ),
		);

		$this->setting->sanitize_value( $value );
	}

	#[Test]
	public function sanitize_value_throws_when_value_is_not_string(): void {

		$this->expectException( InvalidArgumentException::class );

		$this->setting->sanitize_value( 643 );
	}

	#[Test]
	public function render_outputs_currency_field_and_description(): void {

		ob_start();
		$this->setting->render(
			[
				'field_name' => 'fundrik_general_currency',
				'input_id' => 'fundrik_general_currency',
				'value' => 'USD',
			],
		);
		$output = ob_get_clean();

		self::assertSame(
			'<input type="text" name="fundrik_general_currency" class="regular-text"'
			. ' id="fundrik_general_currency" value="USD" maxlength="3">'
			. '<p class="description">Use a 3-letter ISO 4217 currency code such as RUB or USD.</p>',
			$output,
		);
	}

	/**
	 * Returns invalid currency code cases.
	 *
	 * @return iterable<string, array{string}> Invalid currency code cases.
	 */
	public static function invalid_currency_code_cases(): iterable {

		yield 'too short' => [ 'RU' ];
		yield 'too long' => [ 'RUBL' ];
		yield 'non-letter character' => [ 'RU1' ];
	}
}
