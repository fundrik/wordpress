<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\AdminSettings\Groups;

use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsGroupInterface;
use Fundrik\WordPress\Integration\AdminSettings\Settings\AdminSettingInterface;
use Fundrik\WordPress\Integration\AdminSettings\Settings\Checkout\CheckoutCancelUrlSetting;
use Fundrik\WordPress\Integration\AdminSettings\Settings\Checkout\CheckoutSuccessUrlSetting;
use Fundrik\WordPress\Integration\AdminSettings\Settings\General\SelectedGatewaySetting;
use Override;

/**
 * Represents the checkout settings group registered for the Fundrik plugin.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class CheckoutSettingsGroup implements AdminSettingsGroupInterface {

	private const string ID = 'checkout';

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param SelectedGatewaySetting $selected_gateway_setting Provides the selected gateway setting.
	 * @param CheckoutSuccessUrlSetting $success_url_setting Provides the checkout success URL setting.
	 * @param CheckoutCancelUrlSetting $cancel_url_setting Provides the checkout cancel URL setting.
	 */
	public function __construct(
		private SelectedGatewaySetting $selected_gateway_setting,
		private CheckoutSuccessUrlSetting $success_url_setting,
		private CheckoutCancelUrlSetting $cancel_url_setting,
	) {}

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

		return __( 'Checkout', 'fundrik' );
	}

	/**
	 * Renders the settings section description.
	 *
	 * @since 1.0.0
	 */
	#[Override]
	public function render_section_description(): void {

		echo '<p>' . esc_html__( 'Configure checkout settings.', 'fundrik' ) . '</p>';
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
			$this->selected_gateway_setting,
			$this->success_url_setting,
			$this->cancel_url_setting,
		];
	}
}
