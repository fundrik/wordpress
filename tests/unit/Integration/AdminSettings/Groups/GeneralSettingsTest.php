<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\AdminSettings\Groups;

use ArrayObject;
use Brain\Monkey\Functions;
use Fundrik\WordPress\Integration\AdminPages\AdminPageDefinitions;
use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsDefinitions;
use Fundrik\WordPress\Integration\AdminSettings\Groups\GeneralSettings;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( GeneralSettings::class )]
#[UsesClass( AdminPageDefinitions::class )]
#[UsesClass( AdminSettingsDefinitions::class )]
final class GeneralSettingsTest extends WordPressTestCase {

	private GeneralSettings $settings;

	protected function setUp(): void {

		parent::setUp();

		$this->settings = new GeneralSettings();
	}

	#[Test]
	public function register_registers_general_settings_section_and_currency_field(): void {

		$state = new ArrayObject(
			[
				'section_callback' => null,
				'field_callback' => null,
			],
		);

		Functions\expect( 'register_setting' )
			->once()
			->with(
				AdminSettingsDefinitions::OPTION_GROUP,
				GeneralSettings::OPTION_NAME,
				Mockery::on(
					static fn ( array $args ): bool => isset( $args['type'], $args['sanitize_callback'], $args['default'] )
						&& $args['type'] === 'array'
						&& is_callable( $args['sanitize_callback'] )
						&& $args['default'] === [
							GeneralSettings::DEFAULT_CURRENCY_KEY => GeneralSettings::DEFAULT_CURRENCY_DEFAULT,
						],
				),
			)
			->andReturnTrue();

		Functions\expect( 'add_settings_section' )
			->once()
			->with(
				'fundrik_general_settings',
				__( 'General', 'fundrik' ),
				Mockery::on(
					static function ( callable $callback ) use ( $state ): bool {

						$state['section_callback'] = $callback;

						return true;
					},
				),
				AdminPageDefinitions::ROOT_MENU_SLUG,
			)
			->andReturnTrue();

		Functions\expect( 'add_settings_field' )
			->once()
			->with(
				'fundrik_general_settings_currency',
				__( 'Currency', 'fundrik' ),
				Mockery::on(
					static function ( callable $callback ) use ( $state ): bool {

						$state['field_callback'] = $callback;

						return true;
					},
				),
				AdminPageDefinitions::ROOT_MENU_SLUG,
				'fundrik_general_settings',
				[
					'label_for' => 'fundrik_general_settings_currency',
				],
			)
			->andReturnTrue();

		$this->settings->register();

		self::assertIsCallable( $state['section_callback'] );
		self::assertIsCallable( $state['field_callback'] );

		Functions\expect( 'get_option' )
			->once()
			->with(
				GeneralSettings::OPTION_NAME,
				[
					GeneralSettings::DEFAULT_CURRENCY_KEY => GeneralSettings::DEFAULT_CURRENCY_DEFAULT,
				],
			)
			->andReturn(
				[
					GeneralSettings::DEFAULT_CURRENCY_KEY => 'usd',
				],
			);

		ob_start();
		( $state['section_callback'] )();
		( $state['field_callback'] )(
			[
				'label_for' => 'fundrik_general_settings_currency',
			],
		);
		$output = (string) ob_get_clean();

		self::assertStringContainsString( 'Configure global Fundrik settings.', $output );
		self::assertStringContainsString( 'name="fundrik_general_settings[currency]"', $output );
		self::assertStringContainsString( 'value="USD"', $output );
		self::assertStringContainsString( 'Use a 3-letter ISO 4217 currency code such as RUB or USD.', $output );
	}

	#[Test]
	public function register_registers_sanitize_callback_that_accepts_currency_codes(): void {

		$sanitize_callback = $this->capture_sanitize_callback();

		Functions\expect( 'add_settings_error' )->never();

		self::assertSame(
			[
				GeneralSettings::DEFAULT_CURRENCY_KEY => 'USD',
			],
			$sanitize_callback(
				[
					GeneralSettings::DEFAULT_CURRENCY_KEY => 'usd',
				],
			),
		);
	}

	#[Test]
	public function register_registers_sanitize_callback_that_falls_back_to_default_for_invalid_values(): void {

		$sanitize_callback = $this->capture_sanitize_callback();

		Functions\expect( 'add_settings_error' )
			->once()
			->with(
				GeneralSettings::OPTION_NAME,
				GeneralSettings::OPTION_NAME . '_' . GeneralSettings::DEFAULT_CURRENCY_KEY . '_invalid',
				__( 'Currency must be a 3-letter ISO 4217 code.', 'fundrik' ),
			);

		self::assertSame(
			[
				GeneralSettings::DEFAULT_CURRENCY_KEY => GeneralSettings::DEFAULT_CURRENCY_DEFAULT,
			],
			$sanitize_callback(
				[
					GeneralSettings::DEFAULT_CURRENCY_KEY => 'us',
				],
			),
		);
	}

	private function capture_sanitize_callback(): callable {

		$state = new ArrayObject(
			[
				'sanitize_callback' => null,
			],
		);

		Functions\expect( 'register_setting' )
			->once()
			->with(
				AdminSettingsDefinitions::OPTION_GROUP,
				GeneralSettings::OPTION_NAME,
				Mockery::on(
					static function ( array $args ) use ( $state ): bool {

						$state['sanitize_callback'] = $args['sanitize_callback'] ?? null;

						return is_callable( $state['sanitize_callback'] );
					},
				),
			)
			->andReturnTrue();

		Functions\expect( 'add_settings_section' )->once()->andReturnTrue();
		Functions\expect( 'add_settings_field' )->once()->andReturnTrue();

		$this->settings->register();

		self::assertIsCallable( $state['sanitize_callback'] );

		return $state['sanitize_callback'];
	}
}
