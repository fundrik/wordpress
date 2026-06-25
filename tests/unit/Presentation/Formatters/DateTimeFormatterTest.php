<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Presentation\Formatters;

use Brain\Monkey\Functions;
use Fundrik\WordPress\Presentation\Formatters\DateTimeFormatter;
use Fundrik\WordPress\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( DateTimeFormatter::class )]
final class DateTimeFormatterTest extends FundrikTestCase {

	private DateTimeFormatter $formatter;

	protected function setUp(): void {

		parent::setUp();

		Functions\when( 'get_option' )->alias(
			static function ( string $option ) {
				return match ( $option ) {
					'date_format' => 'Y-m-d',
					'time_format' => 'H:i:s',
					default => '',
				};
			},
		);

		Functions\when( 'wp_date' )->alias(
			static fn ( string $format, int $timestamp ): string => gmdate( $format, $timestamp ),
		);

		$this->formatter = new DateTimeFormatter();
	}

	#[Test]
	public function format_trims_microseconds_from_storage_datetime(): void {

		self::assertSame(
			'2026-06-20 10:15:00',
			$this->formatter->format( '2026-06-20 10:15:00.809865' ),
		);
	}

	#[Test]
	public function format_returns_original_value_when_datetime_is_invalid(): void {

		self::assertSame(
			'not-a-date',
			$this->formatter->format( 'not-a-date' ),
		);
	}
}
