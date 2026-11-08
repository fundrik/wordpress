<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\AdminSettings\Groups;

use Fundrik\WordPress\Integration\AdminSettings\Groups\DonationFormSettingsGroup;
use Fundrik\WordPress\Integration\AdminSettings\Settings\DonationForm\DonationFormDefaultAmountLabelSetting;
use Fundrik\WordPress\Integration\AdminSettings\Settings\DonationForm\DonationFormDefaultAmountSetting;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( DonationFormSettingsGroup::class )]
#[UsesClass( DonationFormDefaultAmountLabelSetting::class )]
#[UsesClass( DonationFormDefaultAmountSetting::class )]
final class DonationFormSettingsGroupTest extends WordPressTestCase {

	private DonationFormDefaultAmountSetting $default_amount_setting;
	private DonationFormDefaultAmountLabelSetting $default_amount_label_setting;
	private DonationFormSettingsGroup $group;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->default_amount_setting = new DonationFormDefaultAmountSetting();
		$this->default_amount_label_setting = new DonationFormDefaultAmountLabelSetting();
		$this->group = new DonationFormSettingsGroup(
			$this->default_amount_setting,
			$this->default_amount_label_setting,
		);
	}

	#[Test]
	public function get_id_returns_donation_form(): void {

		self::assertSame( 'donation_form', $this->group->get_id() );
	}

	#[Test]
	public function get_section_title_returns_donation_form(): void {

		self::assertSame( 'Donation Form', $this->group->get_section_title() );
	}

	#[Test]
	public function render_section_description_outputs_donation_form_description(): void {

		ob_start();
		$this->group->render_section_description();
		$output = ob_get_clean();

		self::assertSame( '<p>Configure the defaults used by donation form blocks.</p>', $output );
	}

	#[Test]
	public function get_settings_returns_donation_form_settings_in_registration_order(): void {

		self::assertSame(
			[
				$this->default_amount_setting,
				$this->default_amount_label_setting,
			],
			$this->group->get_settings(),
		);
	}
}
