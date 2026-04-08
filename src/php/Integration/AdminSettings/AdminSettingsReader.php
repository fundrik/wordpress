<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\AdminSettings;

use Fundrik\WordPress\Integration\AdminSettings\Settings\AdminSettingInterface;
use Fundrik\WordPress\Integration\AdminSettings\Settings\Campaign\CampaignDefaultAcceptsDonationsSetting;
use Fundrik\WordPress\Integration\AdminSettings\Settings\Campaign\CampaignDefaultHasTargetSetting;
use Fundrik\WordPress\Integration\AdminSettings\Settings\DonationForm\DonationFormDefaultAmountLabelSetting;
use Fundrik\WordPress\Integration\AdminSettings\Settings\DonationForm\DonationFormDefaultAmountSetting;
use Fundrik\WordPress\Integration\AdminSettings\Settings\General\CurrencySetting;
use Fundrik\WordPress\Integration\Helpers\OptionReader;
use LogicException;
use UnexpectedValueException;

/**
 * Provides access to registered admin settings values.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class AdminSettingsReader {

	/**
	 * The configured admin settings groups.
	 *
	 * @var list<AdminSettingsGroupInterface>
	 */
	private array $admin_setting_groups;

	/**
	 * The indexed settings keyed by setting class.
	 *
	 * @var array<class-string<AdminSettingInterface>, array{group_id: string, setting: AdminSettingInterface}>
	 */
	private array $setting_configs;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param OptionReader $option_reader Provides typed reading helpers for WordPress options.
	 * @param AdminSettingsGroupInterface ...$admin_setting_groups Registered admin settings groups.
	 */
	public function __construct(
		private OptionReader $option_reader,
		AdminSettingsGroupInterface ...$admin_setting_groups,
	) {

		$this->admin_setting_groups = $admin_setting_groups;

		$this->index_settings();
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
	 * Returns the configured default accepts donations value for new campaigns.
	 *
	 * @since 1.0.0
	 *
	 * @return bool Default accepts donations value.
	 */
	public function get_campaign_default_accepts_donations(): bool {

		return $this->get_bool_setting( CampaignDefaultAcceptsDonationsSetting::class );
	}

	/**
	 * Returns the configured default has target value for new campaigns.
	 *
	 * @since 1.0.0
	 *
	 * @return bool Default has target value.
	 */
	public function get_campaign_default_has_target(): bool {

		return $this->get_bool_setting( CampaignDefaultHasTargetSetting::class );
	}

	/**
	 * Returns the configured donation form default amount.
	 *
	 * @since 1.0.0
	 *
	 * @return int Donation form default amount.
	 */
	public function get_donation_form_default_amount(): int {

		return $this->get_int_setting( DonationFormDefaultAmountSetting::class );
	}

	/**
	 * Returns the configured donation form default amount label.
	 *
	 * @since 1.0.0
	 *
	 * @return string Donation form default amount label.
	 */
	public function get_donation_form_default_amount_label(): string {

		return $this->get_string_setting( DonationFormDefaultAmountLabelSetting::class );
	}

	// phpcs:disable SlevomatCodingStandard.Complexity.Cognitive.ComplexityTooHigh
	/**
	 * Indexes all registered setting classes by settings group position.
	 *
	 * @since 1.0.0
	 */
	private function index_settings(): void {

		$setting_configs = [];

		foreach ( $this->admin_setting_groups as $admin_setting_group ) {

			foreach ( $admin_setting_group->get_settings() as $setting ) {

				$setting_class = $setting::class;

				if ( isset( $setting_configs[ $setting_class ] ) ) {
					throw new LogicException(
						sprintf( 'Admin setting "%s" was registered twice.', $setting_class ),
					);
				}

				$setting_configs[ $setting_class ] = [
					'group_id' => $admin_setting_group->get_id(),
					'setting' => $setting,
				];
			}
		}

		$this->setting_configs = $setting_configs;
	}
	// phpcs:enable

	/**
	 * Returns a string setting value.
	 *
	 * @since 1.0.0
	 *
	 * @param string $setting_class Setting class.
	 *
	 * @phpstan-param class-string<AdminSettingInterface> $setting_class Setting class.
	 *
	 * @return string Setting value.
	 */
	private function get_string_setting( string $setting_class ): string {

		$setting = $this->setting_configs[ $setting_class ]['setting'];
		$group_id = $this->setting_configs[ $setting_class ]['group_id'];
		$option_name = $this->get_setting_option_name( $group_id, $setting );
		$default_value = $setting->get_default_value();

		// Fall back to the setting default when the stored option is missing or invalid.
		try {
			return $this->option_reader->find_string_option( $option_name ) ?? $default_value;
		} catch ( UnexpectedValueException ) {
			return $default_value;
		}
	}

	/**
	 * Returns a boolean setting value.
	 *
	 * @since 1.0.0
	 *
	 * @param string $setting_class Setting class.
	 *
	 * @phpstan-param class-string<AdminSettingInterface> $setting_class Setting class.
	 *
	 * @return bool Setting value.
	 */
	private function get_bool_setting( string $setting_class ): bool {

		$setting = $this->setting_configs[ $setting_class ]['setting'];
		$group_id = $this->setting_configs[ $setting_class ]['group_id'];
		$option_name = $this->get_setting_option_name( $group_id, $setting );
		$default_value = $setting->get_default_value();

		// Fall back to the setting default when the stored option is missing or invalid.
		try {
			return $this->option_reader->find_bool_option( $option_name ) ?? $default_value;
		} catch ( UnexpectedValueException ) {
			return $default_value;
		}
	}

	/**
	 * Returns an integer setting value.
	 *
	 * @since 1.0.0
	 *
	 * @param string $setting_class Setting class.
	 *
	 * @phpstan-param class-string<AdminSettingInterface> $setting_class Setting class.
	 *
	 * @return int Setting value.
	 */
	private function get_int_setting( string $setting_class ): int {

		$setting = $this->setting_configs[ $setting_class ]['setting'];
		$group_id = $this->setting_configs[ $setting_class ]['group_id'];
		$option_name = $this->get_setting_option_name( $group_id, $setting );
		$default_value = $setting->get_default_value();

		// Fall back to the setting default when the stored option is missing or invalid.
		try {
			return $this->option_reader->find_int_option( $option_name ) ?? $default_value;
		} catch ( UnexpectedValueException ) {
			return $default_value;
		}
	}

	/**
	 * Returns the setting option name.
	 *
	 * @since 1.0.0
	 *
	 * @param string $group_id Settings group ID.
	 * @param AdminSettingInterface $setting Setting definition.
	 *
	 * @return string Setting option name.
	 */
	private function get_setting_option_name( string $group_id, AdminSettingInterface $setting ): string {

		return sprintf( 'fundrik_%s_%s_setting', $group_id, $setting->get_id() );
	}
}
