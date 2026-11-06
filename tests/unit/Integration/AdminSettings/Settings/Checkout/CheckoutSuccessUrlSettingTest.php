<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\AdminSettings\Settings\Checkout;

use Fundrik\WordPress\Integration\AdminSettings\Settings\Checkout\CheckoutSuccessUrlSetting;
use Fundrik\WordPress\Integration\Helpers\SettingFieldRenderer;
use Fundrik\WordPress\Integration\WpSchemaType;
use Fundrik\WordPress\Tests\WordPressTestCase;
use InvalidArgumentException;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( CheckoutSuccessUrlSetting::class )]
#[UsesClass( SettingFieldRenderer::class )]
final class CheckoutSuccessUrlSettingTest extends WordPressTestCase {

	private CheckoutSuccessUrlSetting $setting;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->setting = new CheckoutSuccessUrlSetting();
	}

	#[Test]
	public function get_id_returns_success_url(): void {

		self::assertSame( 'success_url', $this->setting->get_id() );
	}

	#[Test]
	public function get_label_returns_success_url_label(): void {

		self::assertSame( 'Success URL', $this->setting->get_label() );
	}

	#[Test]
	public function get_default_value_returns_empty_string(): void {

		self::assertSame( '', $this->setting->get_default_value() );
	}

	#[Test]
	public function get_value_type_returns_string_type(): void {

		self::assertSame( WpSchemaType::String, $this->setting->get_value_type() );
	}

	#[Test]
	public function sanitize_value_returns_valid_url(): void {

		$url = 'https://example.com/checkout/success?donation=42';

		self::assertSame( $url, $this->setting->sanitize_value( $url ) );
	}

	#[Test]
	#[DataProvider( 'invalid_url_cases' )]
	public function sanitize_value_throws_for_invalid_url( string $value ): void {

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( sprintf( 'Success URL must be a valid URL. Given: %s.', $value ) );

		$this->setting->sanitize_value( $value );
	}

	#[Test]
	public function sanitize_value_throws_when_value_is_not_string(): void {

		$this->expectException( InvalidArgumentException::class );

		$this->setting->sanitize_value( 42 );
	}

	#[Test]
	public function render_outputs_success_url_field(): void {

		ob_start();
		$this->setting->render(
			[
				'field_name' => 'fundrik_checkout_success_url',
				'input_id' => 'fundrik_checkout_success_url',
				'value' => 'https://example.com/success',
			],
		);
		$output = ob_get_clean();

		self::assertSame(
			'<input type="text" name="fundrik_checkout_success_url" class="regular-text"'
			. ' id="fundrik_checkout_success_url" value="https://example.com/success">',
			$output,
		);
	}

	/**
	 * Returns invalid URL cases.
	 *
	 * @return iterable<string, array{string}> Invalid URL cases.
	 */
	public static function invalid_url_cases(): iterable {

		yield 'empty URL' => [ '' ];
		yield 'relative URL' => [ '/checkout/success' ];
	}
}
