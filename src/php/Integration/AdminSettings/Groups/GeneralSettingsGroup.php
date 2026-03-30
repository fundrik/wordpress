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

	private const string OPTION_NAME = 'fundrik_general_settings';

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
	 * Returns the WordPress option name for the group.
	 *
	 * @since 1.0.0
	 *
	 * @return string Settings option name.
	 */
	#[Override]
	public function get_option_name(): string {

		return self::OPTION_NAME;
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
