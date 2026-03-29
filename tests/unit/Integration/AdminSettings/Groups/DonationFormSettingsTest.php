<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\AdminSettings\Groups;

use ArrayObject;
use Brain\Monkey\Functions;
use Fundrik\WordPress\Integration\AdminPages\AdminPageDefinitions;
use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsDefinitions;
use Fundrik\WordPress\Integration\AdminSettings\Groups\DonationFormSettings;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( DonationFormSettings::class )]
#[UsesClass( AdminPageDefinitions::class )]
#[UsesClass( AdminSettingsDefinitions::class )]
final class DonationFormSettingsTest extends WordPressTestCase {

	private DonationFormSettings $settings;

	protected function setUp(): void {

		parent::setUp();

		$this->settings = new DonationFormSettings();
	}

	#[Test]
	public function register_registers_donation_form_section_and_fields(): void {

		$state = new ArrayObject(
			[
				'section_callback' => null,
				'amount_field_callback' => null,
				'label_field_callback' => null,
			],
		);

		Functions\expect( 'register_setting' )
			->once()
			->with(
				AdminSettingsDefinitions::OPTION_GROUP,
				DonationFormSettings::OPTION_NAME,
				Mockery::on(
					static fn ( array $args ): bool => isset( $args['type'], $args['sanitize_callback'], $args['default'] )
						&& $args['type'] === 'array'
						&& is_callable( $args['sanitize_callback'] )
						&& $args['default'] === [
							DonationFormSettings::DEFAULT_DONATION_AMOUNT_KEY => DonationFormSettings::DEFAULT_DONATION_AMOUNT_DEFAULT,
							DonationFormSettings::DEFAULT_AMOUNT_LABEL_KEY => DonationFormSettings::DEFAULT_AMOUNT_LABEL_DEFAULT,
						],
				),
			)
			->andReturnTrue();

		Functions\expect( 'add_settings_section' )
			->once()
			->with(
				'fundrik_donation_form_settings',
				__( 'Donation Form', 'fundrik' ),
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
				'fundrik_donation_form_settings_default_amount',
				__( 'Default donation amount', 'fundrik' ),
				Mockery::on(
					static function ( callable $callback ) use ( $state ): bool {

						$state['amount_field_callback'] = $callback;

						return true;
					},
				),
				AdminPageDefinitions::ROOT_MENU_SLUG,
				'fundrik_donation_form_settings',
				[
					'label_for' => 'fundrik_donation_form_settings_default_amount',
				],
			)
			->andReturnTrue();

		Functions\expect( 'add_settings_field' )
			->once()
			->with(
				'fundrik_donation_form_settings_default_amount_label',
				__( 'Default amount label', 'fundrik' ),
				Mockery::on(
					static function ( callable $callback ) use ( $state ): bool {

						$state['label_field_callback'] = $callback;

						return true;
					},
				),
				AdminPageDefinitions::ROOT_MENU_SLUG,
				'fundrik_donation_form_settings',
				[
					'label_for' => 'fundrik_donation_form_settings_default_amount_label',
				],
			)
			->andReturnTrue();

		$this->settings->register();

		self::assertIsCallable( $state['section_callback'] );
		self::assertIsCallable( $state['amount_field_callback'] );
		self::assertIsCallable( $state['label_field_callback'] );

		Functions\expect( 'get_option' )
			->twice()
			->with(
				DonationFormSettings::OPTION_NAME,
				[
					DonationFormSettings::DEFAULT_DONATION_AMOUNT_KEY => DonationFormSettings::DEFAULT_DONATION_AMOUNT_DEFAULT,
					DonationFormSettings::DEFAULT_AMOUNT_LABEL_KEY => DonationFormSettings::DEFAULT_AMOUNT_LABEL_DEFAULT,
				],
			)
			->andReturn(
				[
					DonationFormSettings::DEFAULT_DONATION_AMOUNT_KEY => 25,
					DonationFormSettings::DEFAULT_AMOUNT_LABEL_KEY => 'Your amount',
				],
			);

		ob_start();
		( $state['section_callback'] )();
		( $state['amount_field_callback'] )(
			[
				'label_for' => 'fundrik_donation_form_settings_default_amount',
			],
		);
		( $state['label_field_callback'] )(
			[
				'label_for' => 'fundrik_donation_form_settings_default_amount_label',
			],
		);
		$output = (string) ob_get_clean();

		self::assertStringContainsString(
			'Configure the defaults used by donation form blocks when a block does not override them.',
			$output,
		);
		self::assertStringContainsString( 'name="fundrik_donation_form_settings[default_amount]"', $output );
		self::assertStringContainsString( 'value="25"', $output );
		self::assertStringContainsString( 'name="fundrik_donation_form_settings[default_amount_label]"', $output );
		self::assertStringContainsString( 'value="Your amount"', $output );
	}

	#[Test]
	public function register_registers_sanitize_callback_that_accepts_valid_values(): void {

		$sanitize_callback = $this->capture_sanitize_callback();

		Functions\expect( 'add_settings_error' )->never();

		self::assertSame(
			[
				DonationFormSettings::DEFAULT_DONATION_AMOUNT_KEY => 27,
				DonationFormSettings::DEFAULT_AMOUNT_LABEL_KEY => 'Your amount',
			],
			$sanitize_callback(
				[
					DonationFormSettings::DEFAULT_DONATION_AMOUNT_KEY => '27',
					DonationFormSettings::DEFAULT_AMOUNT_LABEL_KEY => '  Your amount  ',
				],
			),
		);
	}

	#[Test]
	public function register_registers_sanitize_callback_that_falls_back_for_invalid_amount(): void {

		$sanitize_callback = $this->capture_sanitize_callback();

		Functions\expect( 'add_settings_error' )
			->once()
			->with(
				DonationFormSettings::OPTION_NAME,
				DonationFormSettings::OPTION_NAME . '_' . DonationFormSettings::DEFAULT_DONATION_AMOUNT_KEY . '_invalid',
				__( 'Default donation amount must be a positive integer.', 'fundrik' ),
			);

		self::assertSame(
			[
				DonationFormSettings::DEFAULT_DONATION_AMOUNT_KEY => DonationFormSettings::DEFAULT_DONATION_AMOUNT_DEFAULT,
				DonationFormSettings::DEFAULT_AMOUNT_LABEL_KEY => 'Your amount',
			],
			$sanitize_callback(
				[
					DonationFormSettings::DEFAULT_DONATION_AMOUNT_KEY => '0',
					DonationFormSettings::DEFAULT_AMOUNT_LABEL_KEY => 'Your amount',
				],
			),
		);
	}

	#[Test]
	public function register_registers_sanitize_callback_that_falls_back_for_invalid_label(): void {

		$sanitize_callback = $this->capture_sanitize_callback();

		Functions\expect( 'add_settings_error' )
			->once()
			->with(
				DonationFormSettings::OPTION_NAME,
				DonationFormSettings::OPTION_NAME . '_' . DonationFormSettings::DEFAULT_AMOUNT_LABEL_KEY . '_invalid',
				__( 'Default amount label must not be empty.', 'fundrik' ),
			);

		self::assertSame(
			[
				DonationFormSettings::DEFAULT_DONATION_AMOUNT_KEY => 27,
				DonationFormSettings::DEFAULT_AMOUNT_LABEL_KEY => DonationFormSettings::DEFAULT_AMOUNT_LABEL_DEFAULT,
			],
			$sanitize_callback(
				[
					DonationFormSettings::DEFAULT_DONATION_AMOUNT_KEY => '27',
					DonationFormSettings::DEFAULT_AMOUNT_LABEL_KEY => '  ',
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
				DonationFormSettings::OPTION_NAME,
				Mockery::on(
					static function ( array $args ) use ( $state ): bool {

						$state['sanitize_callback'] = $args['sanitize_callback'] ?? null;

						return is_callable( $state['sanitize_callback'] );
					},
				),
			)
			->andReturnTrue();

		Functions\expect( 'add_settings_section' )->once()->andReturnTrue();
		Functions\expect( 'add_settings_field' )->twice()->andReturnTrue();

		$this->settings->register();

		self::assertIsCallable( $state['sanitize_callback'] );

		return $state['sanitize_callback'];
	}
}
