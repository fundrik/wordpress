<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\AdminSettings\Settings\DonationForm;

use Fundrik\WordPress\Integration\AdminSettings\Settings\DonationForm\DonationFormDefaultAmountLabelSetting;
use Fundrik\WordPress\Integration\Helpers\SettingFieldRenderer;
use Fundrik\WordPress\Integration\WpSchemaType;
use Fundrik\WordPress\Tests\WordPressTestCase;
use InvalidArgumentException;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( DonationFormDefaultAmountLabelSetting::class )]
#[UsesClass( SettingFieldRenderer::class )]
final class DonationFormDefaultAmountLabelSettingTest extends WordPressTestCase {

	private DonationFormDefaultAmountLabelSetting $setting;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->setting = new DonationFormDefaultAmountLabelSetting();
	}

	#[Test]
	public function get_id_returns_default_amount_label(): void {

		self::assertSame( 'default_amount_label', $this->setting->get_id() );
	}

	#[Test]
	public function get_label_returns_default_amount_label(): void {

		self::assertSame( 'Default amount label', $this->setting->get_label() );
	}

	#[Test]
	public function get_default_value_returns_amount(): void {

		self::assertSame( 'Amount', $this->setting->get_default_value() );
	}

	#[Test]
	public function get_value_type_returns_string_type(): void {

		self::assertSame( WpSchemaType::String, $this->setting->get_value_type() );
	}

	#[Test]
	public function sanitize_value_trims_non_empty_label(): void {

		self::assertSame( 'Contribution', $this->setting->sanitize_value( ' Contribution ' ) );
	}

	#[Test]
	#[DataProvider( 'empty_label_cases' )]
	public function sanitize_value_throws_for_empty_label( string $value ): void {

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Default amount label must not be empty. Given: .' );

		$this->setting->sanitize_value( $value );
	}

	#[Test]
	public function sanitize_value_throws_when_value_is_not_string(): void {

		$this->expectException( InvalidArgumentException::class );

		$this->setting->sanitize_value( 25 );
	}

	#[Test]
	public function render_outputs_default_amount_label_field(): void {

		ob_start();
		$this->setting->render(
			[
				'field_name' => 'fundrik_donation_form_default_amount_label',
				'input_id' => 'fundrik_donation_form_default_amount_label',
				'value' => 'Contribution',
			],
		);
		$output = ob_get_clean();

		self::assertSame(
			'<input type="text" name="fundrik_donation_form_default_amount_label" class="regular-text"'
			. ' id="fundrik_donation_form_default_amount_label" value="Contribution">',
			$output,
		);
	}

	/**
	 * Returns empty default amount label cases.
	 *
	 * @return iterable<string, array{string}> Empty label cases.
	 */
	public static function empty_label_cases(): iterable {

		yield 'empty string' => [ '' ];
		yield 'whitespace only' => [ " \t\n" ];
	}
}
