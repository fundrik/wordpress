<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\AdminSettings\Settings\General;

use Brain\Monkey\Functions;
use Fundrik\WordPress\Integration\AdminSettings\Settings\General\SelectedGatewaySetting;
use Fundrik\WordPress\Integration\Gateways\GatewayInterface;
use Fundrik\WordPress\Integration\Helpers\SettingFieldRenderer;
use Fundrik\WordPress\Integration\WpSchemaType;
use Fundrik\WordPress\Tests\WordPressTestCase;
use InvalidArgumentException;
use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( SelectedGatewaySetting::class )]
#[UsesClass( SettingFieldRenderer::class )]
final class SelectedGatewaySettingTest extends WordPressTestCase {

	private SelectedGatewaySetting $setting;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->setting = new SelectedGatewaySetting(
			$this->make_gateway( 'first_gateway', 'First Gateway' ),
			$this->make_gateway( 'second_gateway', 'Second Gateway' ),
		);
	}

	#[Test]
	public function get_id_returns_selected_gateway(): void {

		self::assertSame( 'selected_gateway', $this->setting->get_id() );
	}

	#[Test]
	public function get_label_returns_selected_gateway_label(): void {

		self::assertSame( 'Selected gateway', $this->setting->get_label() );
	}

	#[Test]
	public function get_default_value_returns_first_gateway_id(): void {

		self::assertSame( 'first_gateway', $this->setting->get_default_value() );
	}

	#[Test]
	public function get_default_value_returns_empty_string_when_no_gateways_are_configured(): void {

		$setting = new SelectedGatewaySetting();

		self::assertSame( '', $setting->get_default_value() );
	}

	#[Test]
	public function get_value_type_returns_string_type(): void {

		self::assertSame( WpSchemaType::String, $this->setting->get_value_type() );
	}

	#[Test]
	public function sanitize_value_returns_configured_gateway_id(): void {

		self::assertSame( 'second_gateway', $this->setting->sanitize_value( ' second_gateway ' ) );
	}

	#[Test]
	public function sanitize_value_returns_default_for_empty_value(): void {

		self::assertSame( 'first_gateway', $this->setting->sanitize_value( '  ' ) );
	}

	#[Test]
	public function sanitize_value_throws_for_unknown_gateway_id(): void {

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage(
			'Active gateway must be one of: first_gateway, second_gateway. Given: unknown_gateway.',
		);

		$this->setting->sanitize_value( 'unknown_gateway' );
	}

	#[Test]
	public function sanitize_value_throws_when_value_is_not_string(): void {

		$this->expectException( InvalidArgumentException::class );

		$this->setting->sanitize_value( 1 );
	}

	#[Test]
	public function render_outputs_gateway_select_and_description(): void {

		Functions\expect( 'selected' )
			->once()
			->with( 'second_gateway', 'first_gateway', false )
			->andReturn( '' );

		Functions\expect( 'selected' )
			->once()
			->with( 'second_gateway', 'second_gateway', false )
			->andReturn( ' selected="selected"' );

		ob_start();
		$this->setting->render(
			[
				'field_name' => 'fundrik_general_selected_gateway',
				'input_id' => 'fundrik_general_selected_gateway',
				'value' => 'second_gateway',
			],
		);
		$output = ob_get_clean();

		self::assertSame(
			'<select name="fundrik_general_selected_gateway" id="fundrik_general_selected_gateway" class="regular-text">'
			. '<option value="first_gateway">First Gateway</option>'
			. '<option value="second_gateway" selected="selected">Second Gateway</option>'
			. '</select><p class="description">Select the gateway used for donation checkout.</p>',
			$output,
		);
	}

	#[Test]
	public function render_outputs_empty_select_when_no_gateways_are_configured(): void {

		$setting = new SelectedGatewaySetting();

		ob_start();
		$setting->render(
			[
				'field_name' => 'fundrik_general_selected_gateway',
				'input_id' => 'fundrik_general_selected_gateway',
				'value' => '',
			],
		);
		$output = ob_get_clean();

		self::assertSame(
			'<select name="fundrik_general_selected_gateway" id="fundrik_general_selected_gateway" class="regular-text">'
			. '</select><p class="description">Select the gateway used for donation checkout.</p>',
			$output,
		);
	}

	private function make_gateway( string $id, string $label ): GatewayInterface&MockInterface {

		$gateway = Mockery::mock( GatewayInterface::class );
		$gateway->shouldReceive( 'get_id' )->andReturn( $id );
		$gateway->shouldReceive( 'get_label' )->andReturn( $label );

		return $gateway;
	}
}
