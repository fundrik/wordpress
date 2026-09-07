<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Helpers;

/**
 * Creates option names for settings.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class SettingOptionName {

	/**
	 * Creates an option name for a settings scope and ID.
	 *
	 * @since 1.0.0
	 *
	 * @param string $scope_id Settings scope ID.
	 * @param string $setting_id Setting ID.
	 *
	 * @return string Option name.
	 */
	public static function create( string $scope_id, string $setting_id ): string {

		return sprintf( 'fundrik_%s_%s_setting', $scope_id, $setting_id );
	}
}
