<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Gateways\SettingFields;

use Fundrik\WordPress\Integration\Gateways\SettingFields\GatewaySettingTextField;
use Fundrik\WordPress\Integration\Helpers\SettingFieldRenderer;
use Fundrik\WordPress\Integration\WpSchemaType;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( GatewaySettingTextField::class )]
#[UsesClass( SettingFieldRenderer::class )]
final class GatewaySettingTextFieldTest extends WordPressTestCase {

	private GatewaySettingTextField $field;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->field = new GatewaySettingTextField( id: 'api_key', label: 'API key', default_value: 'default-key' );
	}

	#[Test]
	public function get_id_returns_configured_id(): void {

		self::assertSame( 'api_key', $this->field->get_id() );
	}

	#[Test]
	public function get_label_returns_configured_label(): void {

		self::assertSame( 'API key', $this->field->get_label() );
	}

	#[Test]
	public function get_default_value_returns_configured_default_value(): void {

		self::assertSame( 'default-key', $this->field->get_default_value() );
	}

	#[Test]
	public function get_value_type_returns_string_type(): void {

		self::assertSame( WpSchemaType::String, $this->field->get_value_type() );
	}

	#[Test]
	public function render_outputs_text_field(): void {

		ob_start();
		$this->field->render(
			[
				'field_name' => 'fundrik_gateway_example_api_key',
				'input_id' => 'fundrik_gateway_example_api_key',
				'value' => 'configured-key',
			],
		);
		$output = ob_get_clean();

		self::assertSame(
			'<input type="text" name="fundrik_gateway_example_api_key" class="regular-text"'
			. ' id="fundrik_gateway_example_api_key" value="configured-key">',
			$output,
		);
	}
}
