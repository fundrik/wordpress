<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\AdminSettings\Groups;

use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsFieldRenderer;
use Fundrik\WordPress\Integration\AdminSettings\Groups\GeneralSettingsGroup;
use Fundrik\WordPress\Integration\AdminSettings\Settings\CurrencySetting;
use Fundrik\WordPress\Tests\WordPressTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( GeneralSettingsGroup::class )]
#[UsesClass( CurrencySetting::class )]
final class GeneralSettingsGroupTest extends WordPressTestCase {

	private GeneralSettingsGroup $settings;

	protected function setUp(): void {

		parent::setUp();

		$this->settings = new GeneralSettingsGroup( new CurrencySetting( new AdminSettingsFieldRenderer() ) );
	}

	#[Test]
	public function it_returns_the_expected_currency_setting(): void {

		$settings = $this->settings->get_settings();
		$currency_setting = $settings[0];

		self::assertCount( 1, $settings );
		self::assertInstanceOf( CurrencySetting::class, $settings[0] );
		self::assertSame( 'currency', $currency_setting->get_key() );
		self::assertSame( 'Currency', $settings[0]->get_label() );
		self::assertSame( 'RUB', $currency_setting->get_default_value() );

		ob_start();
		$currency_setting->render(
			[
				'field_name' => 'fundrik_general_settings[currency]',
				'input_id' => 'fundrik_general_settings_currency',
				'value' => 'RUB',
			]
		);
		$output = (string) ob_get_clean();

		self::assertStringContainsString( 'Use a 3-letter ISO 4217 currency code such as RUB or USD.', $output );
	}

	#[Test]
	public function it_renders_the_section_description(): void {

		ob_start();
		$this->settings->render_section_description();
		$output = (string) ob_get_clean();

		self::assertStringContainsString( 'Configure global Fundrik settings.', $output );
	}

}
