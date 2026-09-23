<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Gateways;

use Fundrik\Core\Components\Donations\Application\Ports\Gateway\DonationGatewayPort;
use Fundrik\WordPress\Integration\Gateways\SettingFields\GatewaySettingFieldInterface;

/**
 * Provides the gateway contract.
 *
 * @since 1.0.0
 */
interface GatewayInterface extends DonationGatewayPort {

	/**
	 * Returns the gateway ID.
	 *
	 * @since 1.0.0
	 *
	 * @return string Gateway ID.
	 */
	public function get_id(): string;

	/**
	 * Returns the gateway label.
	 *
	 * @since 1.0.0
	 *
	 * @return string Gateway label.
	 */
	public function get_label(): string;

	/**
	 * Renders the gateway settings description.
	 *
	 * @since 1.0.0
	 *
	 */
	public function render_settings_description(): void;

	/**
	 * Returns the gateway settings fields.
	 *
	 * @since 1.0.0
	 *
	 * @return list<GatewaySettingFieldInterface> Gateway settings fields.
	 */
	public function get_settings_fields(): array;
}
