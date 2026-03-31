<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\AdminSettings\Groups;

use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsGroupInterface;
use Fundrik\WordPress\Integration\AdminSettings\Settings\AdminSettingInterface;
use Fundrik\WordPress\Integration\AdminSettings\Settings\CurrencySetting;
use Override;

/**
 * Represents the general settings group registered for the Fundrik plugin.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class GeneralSettingsGroup implements AdminSettingsGroupInterface {

	private const string ID = 'fundrik_general_settings';

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param CurrencySetting $currency_setting Provides the currency setting.
	 */
	public function __construct(
		private CurrencySetting $currency_setting,
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

		return __( 'General', 'fundrik' );
	}

	/**
	 * Renders the settings section description.
	 *
	 * @since 1.0.0
	 */
	#[Override]
	public function render_section_description(): void {

		echo '<p>' . esc_html__( 'Configure global Fundrik settings.', 'fundrik' ) . '</p>';
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
			$this->currency_setting,
		];
	}
}
