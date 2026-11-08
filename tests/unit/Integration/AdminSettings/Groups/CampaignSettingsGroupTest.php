<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\AdminSettings\Groups;

use Fundrik\WordPress\Integration\AdminSettings\Groups\CampaignSettingsGroup;
use Fundrik\WordPress\Integration\AdminSettings\Settings\Campaign\CampaignDefaultAcceptsDonationsSetting;
use Fundrik\WordPress\Integration\AdminSettings\Settings\Campaign\CampaignDefaultHasTargetSetting;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( CampaignSettingsGroup::class )]
#[UsesClass( CampaignDefaultAcceptsDonationsSetting::class )]
#[UsesClass( CampaignDefaultHasTargetSetting::class )]
final class CampaignSettingsGroupTest extends WordPressTestCase {

	private CampaignDefaultAcceptsDonationsSetting $default_accepts_donations_setting;
	private CampaignDefaultHasTargetSetting $default_has_target_setting;
	private CampaignSettingsGroup $group;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->default_accepts_donations_setting = new CampaignDefaultAcceptsDonationsSetting();
		$this->default_has_target_setting = new CampaignDefaultHasTargetSetting();
		$this->group = new CampaignSettingsGroup(
			$this->default_accepts_donations_setting,
			$this->default_has_target_setting,
		);
	}

	#[Test]
	public function get_id_returns_campaign(): void {

		self::assertSame( 'campaign', $this->group->get_id() );
	}

	#[Test]
	public function get_section_title_returns_campaign(): void {

		self::assertSame( 'Campaign', $this->group->get_section_title() );
	}

	#[Test]
	public function render_section_description_outputs_campaign_description(): void {

		ob_start();
		$this->group->render_section_description();
		$output = ob_get_clean();

		self::assertSame( '<p>Configure the defaults used for new campaigns.</p>', $output );
	}

	#[Test]
	public function get_settings_returns_campaign_settings_in_registration_order(): void {

		self::assertSame(
			[
				$this->default_accepts_donations_setting,
				$this->default_has_target_setting,
			],
			$this->group->get_settings(),
		);
	}
}
