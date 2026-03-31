<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\AdminSettings\Groups;

use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsGroupInterface;
use Fundrik\WordPress\Integration\AdminSettings\Settings\AdminSettingInterface;
use Fundrik\WordPress\Integration\AdminSettings\Settings\DefaultAmountLabelSetting;
use Fundrik\WordPress\Integration\AdminSettings\Settings\DefaultDonationAmountSetting;
use Override;

/**
 * Represents the donation form settings group registered for the Fundrik plugin.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class DonationFormSettingsGroup implements AdminSettingsGroupInterface {

	private const string ID = 'fundrik_donation_form_settings';

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param DefaultDonationAmountSetting $default_donation_amount_setting Provides default donation amount setting.
	 * @param DefaultAmountLabelSetting $default_amount_label_setting Provides default amount label setting.
	 */
	public function __construct(
		private DefaultDonationAmountSetting $default_donation_amount_setting,
		private DefaultAmountLabelSetting $default_amount_label_setting,
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

		return __( 'Donation Form', 'fundrik' );
	}

	/**
	 * Renders the settings section description.
	 *
	 * @since 1.0.0
	 */
	#[Override]
	public function render_section_description(): void {

		echo '<p>' . esc_html__(
			'Configure the defaults used by donation form blocks when a block does not override them.',
			'fundrik',
		) . '</p>';
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
			$this->default_donation_amount_setting,
			$this->default_amount_label_setting,
		];
	}
}
