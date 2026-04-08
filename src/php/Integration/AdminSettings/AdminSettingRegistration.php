<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\AdminSettings;

use Fundrik\WordPress\Integration\AdminSettings\Settings\AdminSettingInterface;

/**
 * Represents prepared registration data for one admin setting.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class AdminSettingRegistration {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $group_id Settings group ID.
	 * @param string $option_name Setting option name.
	 * @param AdminSettingInterface $setting Setting definition.
	 * @param int|string|bool $current_value Current setting value.
	 */
	public function __construct(
		public string $group_id,
		public string $option_name,
		public AdminSettingInterface $setting,
		public int|string|bool $current_value,
	) {
	}
}
