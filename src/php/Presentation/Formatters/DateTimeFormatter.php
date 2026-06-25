<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Presentation\Formatters;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Formats date and time values for display.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class DateTimeFormatter {

	private const string STORAGE_FORMAT_WITH_MICROSECONDS = 'Y-m-d H:i:s.u';
	private const string STORAGE_FORMAT = 'Y-m-d H:i:s';

	/**
	 * Returns a formatted date-time label for the given storage value.
	 *
	 * @since 1.0.0
	 *
	 * @param string $datetime Storage date-time value.
	 *
	 * @return string Formatted date-time label.
	 */
	public function format( string $datetime ): string {

		$date = DateTimeImmutable::createFromFormat(
			self::STORAGE_FORMAT_WITH_MICROSECONDS,
			$datetime,
			new DateTimeZone( 'UTC' ),
		);

		if ( $date === false ) {
			$date = DateTimeImmutable::createFromFormat(
				self::STORAGE_FORMAT,
				$datetime,
				new DateTimeZone( 'UTC' ),
			);
		}

		if ( $date instanceof DateTimeImmutable ) {
			return wp_date( $this->get_display_format(), $date->getTimestamp() );
		}

		return $datetime;
	}

	/**
	 * Returns the WordPress display format for date and time.
	 *
	 * @since 1.0.0
	 *
	 * @return string Display format.
	 */
	private function get_display_format(): string {

		$date_format = get_option( 'date_format' );
		$time_format = get_option( 'time_format' );

		return sprintf(
			'%s %s',
			is_string( $date_format ) && $date_format !== '' ? $date_format : 'Y-m-d',
			is_string( $time_format ) && $time_format !== '' ? $time_format : 'H:i:s',
		);
	}
}
