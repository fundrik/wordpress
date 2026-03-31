<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\AdminSettings\Groups;

use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsFieldRenderer;
use Fundrik\WordPress\Integration\AdminSettings\Groups\DonationFormSettingsGroup;
use Fundrik\WordPress\Integration\AdminSettings\Settings\DonationForm\DefaultAmountLabelSetting;
use Fundrik\WordPress\Integration\AdminSettings\Settings\DonationForm\DefaultDonationAmountSetting;
use Fundrik\WordPress\Tests\WordPressTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( DonationFormSettingsGroup::class )]
#[UsesClass( DefaultDonationAmountSetting::class )]
#[UsesClass( DefaultAmountLabelSetting::class )]
final class DonationFormSettingsGroupTest extends WordPressTestCase {

	private DonationFormSettingsGroup $settings;

	protected function setUp(): void {

		parent::setUp();

		$this->settings = new DonationFormSettingsGroup(
			new DefaultDonationAmountSetting( new AdminSettingsFieldRenderer() ),
			new DefaultAmountLabelSetting( new AdminSettingsFieldRenderer() ),
		);
	}

	#[Test]
	public function it_returns_the_expected_settings(): void {

		$settings = $this->settings->get_settings();

		self::assertCount( 2, $settings );
		self::assertInstanceOf( DefaultDonationAmountSetting::class, $settings[0] );
		self::assertSame( 'default_amount', $settings[0]->get_id() );
		self::assertSame( 'Default donation amount', $settings[0]->get_label() );
		self::assertSame( 10, $settings[0]->get_default_value() );
		self::assertInstanceOf( DefaultAmountLabelSetting::class, $settings[1] );
		self::assertSame( 'default_amount_label', $settings[1]->get_id() );
		self::assertSame( 'Default amount label', $settings[1]->get_label() );
		self::assertSame( 'Amount', $settings[1]->get_default_value() );
	}

	#[Test]
	public function it_renders_the_section_description(): void {

		ob_start();
		$this->settings->render_section_description();
		$output = (string) ob_get_clean();

		self::assertStringContainsString(
			'Configure the defaults used by donation form blocks when a block does not override them.',
			$output,
		);
	}
}
