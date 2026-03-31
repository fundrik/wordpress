<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\AdminSettings;

use Fundrik\WordPress\Integration\AdminSettings\Settings\AdminSettingInterface;

/**
 * Represents an admin settings group definition.
 *
 * @since 1.0.0
 *
 * @internal
 */
interface AdminSettingsGroupInterface {

	/**
	 * Returns the settings group ID.
	 *
	 * @since 1.0.0
	 *
	 * @return string Group ID.
	 */
	public function get_id(): string;

	/**
	 * Returns the settings section title.
	 *
	 * @since 1.0.0
	 *
	 * @return string Section title.
	 */
	public function get_section_title(): string;

	/**
	 * Renders the settings section description.
	 *
	 * @since 1.0.0
	 */
	public function render_section_description(): void;

	/**
	 * Returns the settings declared within the group.
	 *
	 * @since 1.0.0
	 *
	 * @return list<AdminSettingInterface> Group settings.
	 */
	public function get_settings(): array;
}
