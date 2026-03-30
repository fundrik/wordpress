<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\AdminSettings;

use Fundrik\WordPress\Integration\AdminPages\AdminPageDefinitions;
use Fundrik\WordPress\Integration\AdminSettings\Settings\AdminSettingInterface;

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
	private array $admin_setting_groups;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param AdminSettingsGroupInterface ...$admin_setting_groups Admin settings groups to register.
	 */
	public function __construct( AdminSettingsGroupInterface ...$admin_setting_groups ) {

		$this->admin_setting_groups = $admin_setting_groups;
	}

	/**
	 * Registers all configured admin settings groups.
	 *
	 * @since 1.0.0
	 */
	public function register_all(): void {

		foreach ( $this->admin_setting_groups as $admin_setting_group ) {
			$option_name = $admin_setting_group->get_option_name();
			$settings = $admin_setting_group->get_settings();
			$default_settings = $this->get_default_settings( $settings );
			$current_settings = $this->get_current_settings( $option_name, $settings, $default_settings );

			register_setting(
				AdminPageDefinitions::SETTINGS_PAGE_ID,
				$option_name,
				[
					'type' => 'array',
					'sanitize_callback' => function ( mixed $value ) use ( $option_name, $settings, $default_settings ): array {

						return $this->filter_settings( $option_name, $settings, $default_settings, $value, true );
					},
					'default' => $default_settings,
				],
			);

			add_settings_section(
				$option_name,
				$admin_setting_group->get_section_title(),
				$admin_setting_group->render_section_description( ... ),
				AdminPageDefinitions::ROOT_MENU_SLUG,
			);

			foreach ( $settings as $setting ) {

				$setting_id = $this->get_setting_id( $option_name, $setting );

				add_settings_field(
					$setting_id,
					$setting->get_label(),
					[ $setting, 'render' ],
					AdminPageDefinitions::ROOT_MENU_SLUG,
					$option_name,
					[
						'field_name' => $this->get_setting_name( $option_name, $setting ),
						'input_id' => $setting_id,
						'value' => $current_settings[ $setting->get_key() ] ?? null,
						'label_for' => $setting_id,
					],
				);
			}
		}
	}

	/**
	 * Returns the current normalized settings values.
	 *
	 * @since 1.0.0
	 *
	 * @param string $option_name Settings option name.
	 * @param list<AdminSettingInterface> $settings Setting definitions.
	 * @param array<string, bool|float|int|string|null> $default_settings Default settings values.
	 *
	 * @return array<string, bool|float|int|string|null> Normalized settings values.
	 */
	private function get_current_settings(
		string $option_name,
		array $settings,
		array $default_settings,
	): array {

		return $this->filter_settings(
			$option_name,
			$settings,
			$default_settings,
			get_option( $option_name, $default_settings ),
			false,
		);
	}

	/**
	 * Returns the default settings values for a group.
	 *
	 * @since 1.0.0
	 *
	 * @param list<AdminSettingInterface> $settings Setting definitions.
	 *
	 * @return array<string, bool|float|int|string|null> Default settings values.
	 */
	private function get_default_settings( array $settings ): array {

		$default_settings = [];

		foreach ( $settings as $setting ) {
			$default_settings[ $setting->get_key() ] = $setting->get_default_value();
		}

		return $default_settings;
	}

	/**
	 * Filters raw settings values.
	 *
	 * @since 1.0.0
	 *
	 * @param string $option_name Settings option name.
	 * @param list<AdminSettingInterface> $settings Setting definitions.
	 * @param array<string, bool|float|int|string|null> $default_settings Default settings values.
	 * @param mixed $value Raw settings value.
	 * @param bool $register_errors True when invalid values should register settings errors.
	 *
	 * @return array<string, bool|float|int|string|null> Filtered settings values.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function filter_settings(
		string $option_name,
		array $settings,
		array $default_settings,
		mixed $value,
		bool $register_errors,
	): array {

		$raw_settings = is_array( $value ) ? $value : [];
		$filtered_settings = [];

		foreach ( $settings as $setting ) {
			$raw_value = $raw_settings[ $setting->get_key() ] ?? null;
			$filtered_value = $register_errors
				? $setting->sanitize_value( $raw_value )
				: $setting->normalize_value( $raw_value );

			if ( $filtered_value === null ) {
				if ( $register_errors ) {
					add_settings_error(
						$option_name,
						$option_name . '_' . $setting->get_key() . '_invalid',
						$setting->get_validation_error_message(),
					);
				}

				$filtered_value = $default_settings[ $setting->get_key() ];
			}

			$filtered_settings[ $setting->get_key() ] = $filtered_value;
		}

		return $filtered_settings;
	}

	/**
	 * Returns the HTML form field name for a setting.
	 *
	 * @since 1.0.0
	 *
	 * @param string $option_name Settings option name.
	 * @param AdminSettingInterface $setting Setting definition.
	 *
	 * @return string HTML form field name.
	 */
	private function get_setting_name(
		string $option_name,
		AdminSettingInterface $setting,
	): string {

		return sprintf( '%s[%s]', $option_name, $setting->get_key() );
	}

	/**
	 * Returns the generated setting ID for a settings key.
	 *
	 * @since 1.0.0
	 *
	 * @param string $option_name Settings option name.
	 * @param AdminSettingInterface $setting Setting definition.
	 *
	 * @return string Generated setting ID.
	 */
	private function get_setting_id(
		string $option_name,
		AdminSettingInterface $setting,
	): string {

		return sprintf( '%s_%s', $option_name, $setting->get_key() );
	}

	/**
	 * Returns the number of configured admin settings groups.
	 *
	 * @since 1.0.0
	 *
	 * @return int Admin settings groups count.
	 */
	public function count(): int {

		return count( $this->admin_setting_groups );
	}
}
