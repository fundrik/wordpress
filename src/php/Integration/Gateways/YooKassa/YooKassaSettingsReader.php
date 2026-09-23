<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Gateways\YooKassa;

use Fundrik\WordPress\Integration\Helpers\OptionReader;
use Fundrik\WordPress\Integration\Helpers\SettingOptionName;
use Fundrik\WordPress\Integration\RestApi\RestRouteDefinitions;
use Fundrik\WordPress\Integration\RestApi\Routes\YooKassaNotifyRestRoute;
use UnexpectedValueException;

/**
 * Provides access to YooKassa settings values.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class YooKassaSettingsReader {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param OptionReader $option_reader Provides typed reading helpers for WordPress options.
	 */
	public function __construct(
		private OptionReader $option_reader,
	) {}

	/**
	 * Returns the configured YooKassa shop ID.
	 *
	 * @since 1.0.0
	 *
	 * @return string YooKassa shop ID.
	 */
	public function get_shop_id(): string {

		return $this->get_string( 'shop_id' );
	}

	/**
	 * Returns the configured YooKassa secret key.
	 *
	 * @since 1.0.0
	 *
	 * @return string YooKassa secret key.
	 */
	public function get_secret_key(): string {

		return $this->get_string( 'secret_key' );
	}

	/**
	 * Returns the configured webhook URL.
	 *
	 * @since 1.0.0
	 *
	 * @return string Webhook URL, if configured.
	 */
	public function get_webhook_url(): string {

		return RestRouteDefinitions::get_route_url( YooKassaNotifyRestRoute::class );
	}

	/**
	 * Returns a string setting value.
	 *
	 * @since 1.0.0
	 *
	 * @template T of string|bool
	 *
	 * @param string $setting_id Setting ID.
	 *
	 * @return string Setting value.
	 */
	private function get_string( string $setting_id ): string {

		$option_name = SettingOptionName::create( 'yookassa', $setting_id );

		try {
			return $this->option_reader->find_string_option( $option_name ) ?? '';
		} catch ( UnexpectedValueException ) {
			return '';
		}
	}

}
