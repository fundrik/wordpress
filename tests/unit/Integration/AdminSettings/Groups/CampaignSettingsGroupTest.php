<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\AdminSettings\Groups;

use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsFieldRenderer;
use Fundrik\WordPress\Integration\AdminSettings\Groups\CampaignSettingsGroup;
use Fundrik\WordPress\Integration\AdminSettings\Settings\Campaign\CampaignDefaultAcceptsDonationsSetting;
use Fundrik\WordPress\Integration\AdminSettings\Settings\Campaign\CampaignDefaultHasTargetSetting;
use Fundrik\WordPress\Tests\WordPressTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( CampaignSettingsGroup::class )]
#[UsesClass( CampaignDefaultAcceptsDonationsSetting::class )]
#[UsesClass( CampaignDefaultHasTargetSetting::class )]
final class CampaignSettingsGroupTest extends WordPressTestCase {

	private CampaignSettingsGroup $settings;

	protected function setUp(): void {

		parent::setUp();

		$this->settings = new CampaignSettingsGroup(
			new CampaignDefaultAcceptsDonationsSetting( new AdminSettingsFieldRenderer() ),
			new CampaignDefaultHasTargetSetting( new AdminSettingsFieldRenderer() ),
		);
	}

	#[Test]
	public function it_returns_the_expected_settings(): void {

		$settings = $this->settings->get_settings();

		self::assertCount( 2, $settings );
		self::assertInstanceOf( CampaignDefaultAcceptsDonationsSetting::class, $settings[0] );
		self::assertSame( 'default_accepts_donations', $settings[0]->get_id() );
		self::assertSame( 'Default accepts donations', $settings[0]->get_label() );
		self::assertTrue( $settings[0]->get_default_value() );
		self::assertInstanceOf( CampaignDefaultHasTargetSetting::class, $settings[1] );
		self::assertSame( 'default_has_target', $settings[1]->get_id() );
		self::assertSame( 'Default has target', $settings[1]->get_label() );
		self::assertFalse( $settings[1]->get_default_value() );
	}

	#[Test]
	public function it_renders_the_section_description(): void {

		ob_start();
		$this->settings->render_section_description();
		$output = (string) ob_get_clean();

		self::assertStringContainsString(
			'Configure the defaults used for new campaigns.',
			$output,
		);
	}
}
