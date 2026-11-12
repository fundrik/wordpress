<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Gateways\SettingFields;

use Brain\Monkey\Functions;
use Fundrik\WordPress\Integration\Gateways\SettingFields\GatewaySettingCheckboxField;
use Fundrik\WordPress\Integration\Helpers\SettingFieldRenderer;
use Fundrik\WordPress\Integration\WpSchemaType;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( GatewaySettingCheckboxField::class )]
#[UsesClass( SettingFieldRenderer::class )]
final class GatewaySettingCheckboxFieldTest extends WordPressTestCase {

	private GatewaySettingCheckboxField $field;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->field = new GatewaySettingCheckboxField( id: 'test_mode', label: 'Use test mode', default_value: false );
	}

	#[Test]
	public function get_id_returns_configured_id(): void {

		self::assertSame( 'test_mode', $this->field->get_id() );
	}

	#[Test]
	public function get_label_returns_configured_label(): void {

		self::assertSame( 'Use test mode', $this->field->get_label() );
	}

	#[Test]
	public function get_default_value_returns_configured_default_value(): void {

		self::assertFalse( $this->field->get_default_value() );
	}

	#[Test]
	public function get_value_type_returns_boolean_type(): void {

		self::assertSame( WpSchemaType::Boolean, $this->field->get_value_type() );
	}

	#[Test]
	#[DataProvider( 'render_value_cases' )]
	public function render_outputs_checkbox_field( bool $value, string $checked_attribute ): void {

		Functions\expect( 'checked' )
			->once()
			->with( $value, true, false )
			->andReturn( $checked_attribute );

		ob_start();
		$this->field->render(
			[
				'field_name' => 'fundrik_gateway_example_test_mode',
				'input_id' => 'fundrik_gateway_example_test_mode',
				'value' => $value,
			],
		);
		$output = ob_get_clean();

		self::assertSame(
			'<input type="hidden" name="fundrik_gateway_example_test_mode" value="0">'
			. '<label for="fundrik_gateway_example_test_mode">'
			. '<input type="checkbox" name="fundrik_gateway_example_test_mode"'
			. ' id="fundrik_gateway_example_test_mode" value="1"'
			. $checked_attribute
			. '> Use test mode</label>',
			$output,
		);
	}

	/**
	 * Returns checkbox rendering cases.
	 *
	 * @return iterable<string, array{bool, string}> Checkbox rendering cases.
	 */
	public static function render_value_cases(): iterable {

		yield 'checked' => [ true, ' checked="checked"' ];
		yield 'unchecked' => [ false, '' ];
	}
}
