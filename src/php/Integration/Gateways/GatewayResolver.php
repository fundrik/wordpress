<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Gateways;

use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsReader;
use Fundrik\WordPress\Integration\Helpers\OptionReader;
use Fundrik\WordPress\Integration\Helpers\SettingOptionName;
use UnexpectedValueException;

/**
 * Resolves the active gateway from configured gateway instances.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class GatewayResolver {

	/**
	 * Gateway instances.
	 *
	 * @var list<GatewayInterface>
	 */
	private array $gateways;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param AdminSettingsReader $admin_settings_reader Provides registered admin settings values.
	 * @param OptionReader $option_reader Provides typed helpers for WordPress options.
	 * @param GatewayInterface ...$gateways Gateway instances.
	 */
	public function __construct(
		private AdminSettingsReader $admin_settings_reader,
		private OptionReader $option_reader,
		GatewayInterface ...$gateways,
	) {

		$this->gateways = $gateways;
	}

	/**
	 * Returns the active gateway.
	 *
	 * @since 1.0.0
	 *
	 * @return GatewayInterface Active gateway.
	 *
	 * @throws GatewayResolutionException When the selected gateway is not configured or enabled.
	 */
	public function resolve_active_gateway(): GatewayInterface {

		$selected_gateway_id = $this->admin_settings_reader->get_selected_gateway_id();

		foreach ( $this->gateways as $gateway ) {

			if ( $gateway->get_id() !== $selected_gateway_id ) {
				continue;
			}

			if ( ! $this->is_enabled( $gateway ) ) {
				throw new GatewayResolutionException(
					sprintf( 'Cannot resolve gateway "%s": gateway is disabled.', $selected_gateway_id ),
				);
			}

			return $gateway;
		}

		throw new GatewayResolutionException(
			sprintf( 'Cannot resolve gateway "%s": gateway is not registered.', $selected_gateway_id ),
		);
	}

	/**
	 * Checks whether a gateway is enabled.
	 *
	 * @since 1.0.0
	 *
	 * @param GatewayInterface $gateway Gateway definition.
	 *
	 * @return bool True when the gateway is enabled.
	 */
	private function is_enabled( GatewayInterface $gateway ): bool {

		$option_name = SettingOptionName::create( $gateway->get_id(), 'enabled' );

		try {
			return $this->option_reader->find_bool_option( $option_name ) ?? false;
		} catch ( UnexpectedValueException ) {
			return false;
		}
	}
}
