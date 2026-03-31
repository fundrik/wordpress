<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\AdminSettings;

use Fundrik\WordPress\Integration\AdminPages\AdminPageDefinitions;
use Fundrik\WordPress\Integration\AdminSettings\Settings\AdminSettingInterface;
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

			$group_id = $group->get_id();
			$settings = $group->get_settings();
			$setting_states = $this->get_group_setting_states( $group_id, $settings );
			$default_settings = $this->get_setting_value_map( $setting_states, 'default_value' );

			register_setting(
				AdminPageDefinitions::SETTINGS_PAGE_ID,
				$group_id,
				[
					'type' => 'array',
					'sanitize_callback' => fn ( mixed $value ): array => $this->filter_settings(
						$group_id,
						$setting_states,
						$value,
						true,
					),
					'default' => $default_settings,
				],
			);

			add_settings_section(
				$group_id,
				$group->get_section_title(),
				$group->render_section_description( ... ),
				AdminPageDefinitions::ROOT_MENU_SLUG,
			);

			foreach ( $setting_states as $setting_state ) {

				$setting = $setting_state['setting'];

				$setting_id = $this->get_setting_id( $group_id, $setting );

				add_settings_field(
					$setting_id,
					$setting->get_label(),
					$setting->render( ... ),
					AdminPageDefinitions::ROOT_MENU_SLUG,
					$group_id,
					[
						'field_name' => $this->get_setting_name( $group_id, $setting ),
						'input_id' => $setting_id,
						'value' => $setting_state['current_value'],
						'label_for' => $setting_id,
					],
				);
			}
		}
	}

	/**
	 * Returns the settings state for a group.
	 *
	 * @since 1.0.0
	 *
	 * @param AdminSettingsGroupInterface $group Settings group definition.
	 *
	 * @return list<array{
	 *     setting: AdminSettingInterface,
	 *     default_value: bool|float|int|string|null,
	 *     current_value: bool|float|int|string|null
	 * }> Settings state.
	 */
	private function get_group_setting_states( string $group_id, array $settings ): array {

		$default_settings = $this->get_default_settings( $settings );
		$current_settings = $this->get_current_settings( $group_id, $settings, $default_settings );
		$setting_states = [];

		foreach ( $settings as $setting ) {

			$setting_id = $setting->get_id();

			$setting_states[] = [
				'setting' => $setting,
				'default_value' => $default_settings[ $setting_id ],
				'current_value' => $current_settings[ $setting_id ],
			];
		}

		return $setting_states;
	}

	/**
	 * Returns the current normalized settings values.
	 *
	 * @since 1.0.0
	 *
	 * @param string $group_id Settings group ID.
	 * @param list<AdminSettingInterface> $settings Setting definitions.
	 * @param array<string, bool|float|int|string|null> $default_settings Default settings values.
	 *
	 * @return array<string, bool|float|int|string|null> Normalized settings values.
	 */
	private function get_current_settings( string $group_id, array $settings, array $default_settings, ): array {

		$setting_states = [];

		foreach ( $settings as $setting ) {

			$setting_id = $setting->get_id();

			$setting_states[] = [
				'setting' => $setting,
				'default_value' => $default_settings[ $setting_id ],
				'current_value' => $default_settings[ $setting_id ],
			];
		}

		return $this->filter_settings(
			$group_id,
			$setting_states,
			get_option( $group_id, $default_settings ),
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
			$default_settings[ $setting->get_id() ] = $setting->get_default_value();
		}

		return $default_settings;
	}

	/**
	 * Returns a value map for the given setting states.
	 *
	 * @since 1.0.0
	 *
	 * @param list<array{
	 *     setting: AdminSettingInterface,
	 *     default_value: bool|float|int|string|null,
	 *     current_value: bool|float|int|string|null
	 * }> $setting_states Settings state.
	 * @param 'default_value'|'current_value' $value_key Value key.
	 *
	 * @return array<string, bool|float|int|string|null> Value map.
	 */
	private function get_setting_value_map( array $setting_states, string $value_key ): array {

		$setting_values = [];

		foreach ( $setting_states as $setting_state ) {

			$setting_values[ $setting_state['setting']->get_id() ] = $setting_state[ $value_key ];
		}

		return $setting_values;
	}

	/**
	 * Filters raw settings values.
	 *
	 * @since 1.0.0
	 *
	 * @param string $group_id Settings group ID.
	 * @param list<array{
	 *     setting: AdminSettingInterface,
	 *     default_value: bool|float|int|string|null,
	 *     current_value: bool|float|int|string|null
	 * }> $setting_states Settings state.
	 * @param mixed $value Raw settings value.
	 * @param bool $register_errors True when invalid values should register settings errors.
	 *
	 * @return array<string, bool|float|int|string|null> Filtered settings values.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function filter_settings(
		string $group_id,
		array $setting_states,
		mixed $value,
		bool $register_errors,
	): array {

		$raw_settings = is_array( $value ) ? $value : [];
		$filtered_settings = [];

		foreach ( $setting_states as $setting_state ) {

			$setting = $setting_state['setting'];
			$raw_value = $raw_settings[ $setting->get_id() ] ?? null;

			try {
				$filtered_value = $register_errors
					? $setting->sanitize_value( $raw_value )
					: $setting->normalize_value( $raw_value );
			} catch ( InvalidArgumentException $exception ) {

				if ( $register_errors ) {
					add_settings_error(
						$group_id,
						$group_id . '_' . $setting->get_id() . '_invalid',
						$exception->getMessage(),
					);
				}

				$filtered_value = $register_errors
					? $setting_state['current_value']
					: $setting_state['default_value'];
			}

			$filtered_settings[ $setting->get_id() ] = $filtered_value;
		}

		return $filtered_settings;
	}

	/**
	 * Returns the HTML form field name for a setting.
	 *
	 * @since 1.0.0
	 *
	 * @param string $group_id Settings group ID.
	 * @param AdminSettingInterface $setting Setting definition.
	 *
	 * @return string HTML form field name.
	 */
	private function get_setting_name( string $group_id, AdminSettingInterface $setting, ): string {

		return sprintf( '%s[%s]', $group_id, $setting->get_id() );
	}

	/**
	 * Returns the generated setting ID for a settings key.
	 *
	 * @since 1.0.0
	 *
	 * @param string $group_id Settings group ID.
	 * @param AdminSettingInterface $setting Setting definition.
	 *
	 * @return string Generated setting ID.
	 */
	private function get_setting_id( string $group_id, AdminSettingInterface $setting, ): string {

		return sprintf( '%s_%s', $group_id, $setting->get_id() );
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
