<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\AdminSettings\Groups;

use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsGroupInterface;
use Fundrik\WordPress\Integration\AdminSettings\Settings\AdminSettingInterface;
use Fundrik\WordPress\Integration\AdminSettings\Settings\Campaign\CampaignDefaultAcceptsDonationsSetting;
use Fundrik\WordPress\Integration\AdminSettings\Settings\Campaign\CampaignDefaultHasTargetSetting;
use Override;

/**
 * Represents the campaign settings group registered for the Fundrik plugin.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class CampaignSettingsGroup implements AdminSettingsGroupInterface {

	private const string ID = 'campaign';

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param CampaignDefaultAcceptsDonationsSetting $default_accepts_donations_setting Provides the default accepts donations setting.
	 * @param CampaignDefaultHasTargetSetting $default_has_target_setting Provides the default has target setting.
	 */
	public function __construct(
		private CampaignDefaultAcceptsDonationsSetting $default_accepts_donations_setting,
		private CampaignDefaultHasTargetSetting $default_has_target_setting,
	) {
	}

	/**
	 * Returns the group ID.
	 *
	 * @since 1.0.0
	 *
	 * @return string Group ID.
	 */
	#[Override]
	public function get_id(): string {

		return self::ID;
	}

	/**
	 * Returns the section title displayed on the settings page.
	 *
	 * @since 1.0.0
	 *
	 * @return string Section title.
	 */
	#[Override]
	public function get_section_title(): string {

		return __( 'Campaign', 'fundrik' );
	}

	/**
	 * Renders the settings section description.
	 *
	 * @since 1.0.0
	 */
	#[Override]
	public function render_section_description(): void {

		echo '<p>' . esc_html__( 'Configure the defaults used for new campaigns.', 'fundrik' ) . '</p>';
	}

	/**
	 * Returns the settings declared within the group.
	 *
	 * @since 1.0.0
	 *
	 * @return list<AdminSettingInterface> Group settings.
	 */
	#[Override]
	public function get_settings(): array {

		return [
			$this->default_accepts_donations_setting,
			$this->default_has_target_setting,
		];
	}
}
