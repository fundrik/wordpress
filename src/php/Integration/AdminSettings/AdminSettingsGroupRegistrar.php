<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\AdminSettings;

use Fundrik\WordPress\Integration\AdminPages\AdminPageDefinitions;
use Fundrik\WordPress\Integration\AdminSettings\Settings\AdminSettingInterface;
use Fundrik\WordPress\Integration\Helpers\OptionReader;
use InvalidArgumentException;

/**
 * Registers all configured admin settings groups in WordPress.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class AdminSettingsGroupRegistrar {

	/**
	 * The configured admin settings groups.
	 *
	 * @var list<AdminSettingsGroupInterface>
	 */
	private array $groups;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param AdminSettingsGroupInterface ...$groups Admin settings groups to register.
	 */
	public function __construct( AdminSettingsGroupInterface ...$groups ) {

		$this->groups = $groups;
	}

	/**
	 * Registers all configured admin settings groups.
	 *
	 * @since 1.0.0
	 */
	public function register_all(): void {

		foreach ( $this->groups as $group ) {

			$this->register_section( $group );

			foreach ( $group->get_settings() as $setting ) {

				$registration = $this->prepare_setting_registration( $group, $setting );

				$this->register_setting_field( $registration );
				$this->register_setting_option( $registration );
			}
		}
	}

	/**
	 * Registers an admin settings section.
	 *
	 * @since 1.0.0
	 *
	 * @param AdminSettingsGroupInterface $group Settings group definition.
	 */
	private function register_section( AdminSettingsGroupInterface $group ): void {

		add_settings_section(
			$group->get_id(),
			$group->get_section_title(),
			$group->render_section_description( ... ),
			AdminPageDefinitions::ROOT_MENU_SLUG,
		);
	}

	/**
	 * Prepares registration data for an admin setting.
	 *
	 * @since 1.0.0
	 *
	 * @param AdminSettingsGroupInterface $group Settings group definition.
	 * @param AdminSettingInterface $setting Setting definition.
	 *
	 * @return AdminSettingRegistration Prepared registration data.
	 */
	private function prepare_setting_registration(
		AdminSettingsGroupInterface $group,
		AdminSettingInterface $setting,
	): AdminSettingRegistration {

		$group_id = $group->get_id();
		$option_name = sprintf( 'fundrik_%s_%s_setting', $group_id, $setting->get_id() );

		$current_value = OptionReader::get_option_or_default(
			$option_name,
			$setting->get_value_type(),
			$setting->get_default_value(),
		);

		return new AdminSettingRegistration(
			group_id: $group_id,
			option_name: $option_name,
			setting: $setting,
			current_value: $current_value,
		);
	}

	/**
	 * Registers an admin settings field.
	 *
	 * @since 1.0.0
	 *
	 * @param AdminSettingRegistration $registration Prepared registration data.
	 */
	private function register_setting_field( AdminSettingRegistration $registration ): void {

		add_settings_field(
			$registration->option_name,
			$registration->setting->get_label(),
			$registration->setting->render( ... ),
			AdminPageDefinitions::ROOT_MENU_SLUG,
			$registration->group_id,
			[
				'field_name' => $registration->option_name,
				'input_id' => $registration->option_name,
				'value' => $registration->current_value,
				'label_for' => $registration->option_name,
			],
		);
	}

	/**
	 * Registers an admin setting option.
	 *
	 * @since 1.0.0
	 *
	 * @param AdminSettingRegistration $registration Prepared registration data.
	 */
	private function register_setting_option( AdminSettingRegistration $registration ): void {

		register_setting(
			AdminPageDefinitions::SETTINGS_PAGE_ID,
			$registration->option_name,
			[
				'type' => $registration->setting->get_value_type()->value,
				'sanitize_callback' => fn ( mixed $value ): int|string => $this->sanitize_registered_setting_value(
					$registration,
					$value,
				),
				'default' => $registration->setting->get_default_value(),
			],
		);
	}

	/**
	 * Sanitizes a registered setting value.
	 *
	 * @since 1.0.0
	 *
	 * @param AdminSettingRegistration $registration Prepared registration data.
	 * @param mixed $value Raw setting value.
	 *
	 * @return int|string Sanitized setting value.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function sanitize_registered_setting_value(
		AdminSettingRegistration $registration,
		mixed $value,
	): int|string {

		try {
			return $registration->setting->sanitize_value( $value );
		} catch ( InvalidArgumentException $exception ) {
			add_settings_error(
				$registration->option_name,
				$registration->option_name . '_invalid',
				$exception->getMessage(),
			);

			return $registration->current_value;
		}
	}

	/**
	 * Returns the number of configured admin settings groups.
	 *
	 * @since 1.0.0
	 *
	 * @return int Admin settings groups count.
	 */
	public function count(): int {

		return count( $this->groups );
	}
}
