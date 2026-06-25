<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests;

use Brain\Monkey;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

abstract class WordPressTestCase extends FundrikTestCase {

	use MockeryPHPUnitIntegration;

	protected function setUp(): void {

		parent::setUp();

		Monkey\setUp();

		Monkey\Functions\stubEscapeFunctions();
		Monkey\Functions\stubTranslationFunctions();
		Monkey\Functions\when( 'get_option' )->alias(
			static fn ( string $option ) => match ( $option ) {
				'date_format' => 'Y-m-d',
				'time_format' => 'H:i:s',
				default => '',
			},
		);
		Monkey\Functions\when( 'wp_date' )->alias(
			static fn ( string $format, int $timestamp ): string => gmdate( $format, $timestamp ),
		);
		Monkey\Functions\when( 'number_format_i18n' )->alias(
			static fn ( float $number, int $decimals = 0 ): string => number_format( $number, $decimals, '.', ',' ),
		);
	}

	protected function tearDown(): void {

		Monkey\tearDown();

		parent::tearDown();
	}
}
