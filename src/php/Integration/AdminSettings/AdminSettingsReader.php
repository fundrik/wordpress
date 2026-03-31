<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\AdminSettings;

use Fundrik\WordPress\Integration\AdminSettings\Settings\AdminSettingInterface;
use Fundrik\WordPress\Integration\AdminSettings\Settings\CurrencySetting;
use Fundrik\WordPress\Integration\AdminSettings\Settings\DefaultAmountLabelSetting;
use Fundrik\WordPress\Integration\AdminSettings\Settings\DefaultDonationAmountSetting;
use InvalidArgumentException;
use LogicException;

/**
 * Provides access to registered admin settings values.
 *
 * @since 1.0.0
 *
 * @internal
 */
class AdminSettingsReader {

	/**
	 * The configured admin settings groups.
	 *
	 * @var list<AdminSettingsGroupInterface>
	 */
	private array $admin_setting_groups;

	/**
	 * The indexed settings group positions keyed by setting class.
	 *
	 * @var array<class-string<AdminSettingInterface>, int>
	 */
	private array $setting_group_indexes = [];

	/**
	 * The resolved settings values keyed by group position and setting class.
	 *
	 * @var array<int, array<class-string<AdminSettingInterface>, bool|float|int|string|null>>
	 */
	private array $group_settings_values = [];

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param AdminSettingsGroupInterface ...$admin_setting_groups Registered admin settings groups.
	 */
	public function __construct( AdminSettingsGroupInterface ...$admin_setting_groups ) {

		$this->admin_setting_groups = $admin_setting_groups;
		$this->index_settings();
	}

	/**
	 * Returns the resolved value for a registered setting class.
	 *
	 * @since 1.0.0
	 *
	 * @param class-string<AdminSettingInterface> $setting_class Setting class.
	 *
	 * @return bool|float|int|string|null Resolved setting value.
	 */
	private function get( string $setting_class ): bool|float|int|string|null {

		if ( ! isset( $this->setting_group_indexes[ $setting_class ] ) ) {
			throw new InvalidArgumentException(
				sprintf( 'Unknown admin setting "%s".', $setting_class ),
			);
		}

		$group_index = $this->setting_group_indexes[ $setting_class ];
		$this->load_group_settings_values( $group_index );

		return $this->group_settings_values[ $group_index ][ $setting_class ];
	}

	/**
	 * Returns the configured currency value.
	 *
	 * @since 1.0.0
	 *
	 * @return string Currency code.
	 */
	public function get_currency(): string {

		return $this->get_string_setting( CurrencySetting::class );
	}

	/**
	 * Returns the configured default donation amount.
	 *
	 * @since 1.0.0
	 *
	 * @return int Default donation amount.
	 */
	public function get_default_donation_amount(): int {

		return $this->get_int_setting( DefaultDonationAmountSetting::class );
	}

	/**
	 * Returns the configured default amount label.
	 *
	 * @since 1.0.0
	 *
	 * @return string Default amount label.
	 */
	public function get_default_amount_label(): string {

		return $this->get_string_setting( DefaultAmountLabelSetting::class );
	}

	/**
	 * Indexes all registered setting classes by settings group position.
	 *
	 * @since 1.0.0
	 */
	private function index_settings(): void {

		foreach ( $this->admin_setting_groups as $group_index => $admin_setting_group ) {

			foreach ( $admin_setting_group->get_settings() as $setting ) {
				$setting_class = $setting::class;

				if ( isset( $this->setting_group_indexes[ $setting_class ] ) ) {
					throw new LogicException(
						sprintf( 'Admin setting "%s" was registered twice.', $setting_class ),
					);
				}

				$this->setting_group_indexes[ $setting_class ] = $group_index;
			}
		}
	}

	/**
	 * Loads the resolved values for a settings group.
	 *
	 * @since 1.0.0
	 *
	 * @param int $group_index Settings group position.
	 */
	private function load_group_settings_values( int $group_index ): void {

		if ( isset( $this->group_settings_values[ $group_index ] ) ) {
			return;
		}

		$admin_setting_group = $this->admin_setting_groups[ $group_index ];
		$settings = $admin_setting_group->get_settings();
		$default_settings = $this->get_default_settings( $settings );
		$stored_settings = get_option( $admin_setting_group->get_id(), $default_settings );
		$raw_settings = is_array( $stored_settings ) ? $stored_settings : [];
		$group_settings_values = [];

		foreach ( $settings as $setting ) {
			$raw_value = $raw_settings[ $setting->get_id() ] ?? null;
			try {
				$resolved_value = $setting->normalize_value( $raw_value );
			} catch ( InvalidArgumentException ) {
				$resolved_value = null;
			}

			if ( $resolved_value === null ) {
				$resolved_value = $default_settings[ $setting->get_id() ];
			}

			$group_settings_values[ $setting::class ] = $resolved_value;
		}

		$this->group_settings_values[ $group_index ] = $group_settings_values;
	}

	/**
	 * Returns the default settings values for a settings group.
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
	 * Returns a string setting value.
	 *
	 * @since 1.0.0
	 *
	 * @param class-string<AdminSettingInterface> $setting_class Setting class.
	 *
	 * @return string Setting value.
	 */
	private function get_string_setting( string $setting_class ): string {

		$setting_value = $this->get( $setting_class );

		if ( ! is_string( $setting_value ) ) {
			throw new LogicException(
				sprintf( 'Admin setting "%s" returned an invalid string value.', $setting_class ),
			);
		}

		return $setting_value;
	}

	/**
	 * Returns an integer setting value.
	 *
	 * @since 1.0.0
	 *
	 * @param class-string<AdminSettingInterface> $setting_class Setting class.
	 *
	 * @return int Setting value.
	 */
	private function get_int_setting( string $setting_class ): int {

		$setting_value = $this->get( $setting_class );

		if ( ! is_int( $setting_value ) ) {
			throw new LogicException(
				sprintf( 'Admin setting "%s" returned an invalid integer value.', $setting_class ),
			);
		}

		return $setting_value;
	}
}
