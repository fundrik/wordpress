<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Presentation\Formatters;

/**
 * Formats money amounts.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class MoneyFormatter {

	/**
	 * Returns a formatted money label for the given minor-unit amount.
	 *
	 * @since 1.0.0
	 *
	 * @param int $amount_minor Amount in minor units.
	 * @param string $currency_code Currency code.
	 *
	 * @return string Formatted amount label.
	 */
	public function format( int $amount_minor, string $currency_code ): string {

		return sprintf(
			'%s %s',
			number_format_i18n( $amount_minor / 100, 2 ),
			strtoupper( $currency_code ),
		);
	}
}
