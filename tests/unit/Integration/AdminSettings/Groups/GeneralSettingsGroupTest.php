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

		self::assertCount( 1, $settings );
		self::assertInstanceOf( CurrencySetting::class, $settings[0] );
		self::assertSame( CurrencySetting::KEY, $settings[0]->get_key() );
		self::assertSame( 'Currency', $settings[0]->get_label() );
		self::assertSame( 'Use a 3-letter ISO 4217 currency code such as RUB or USD.', $settings[0]->get_description() );
		self::assertSame( CurrencySetting::DEFAULT_VALUE, $settings[0]->get_default_value() );
	}

	#[Test]
	public function it_renders_the_section_description(): void {

		ob_start();
		$this->settings->render_section_description();
		$output = (string) ob_get_clean();

		self::assertStringContainsString( 'Configure global Fundrik settings.', $output );
	}

}
