<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests;

use Brain\Monkey;
use DateTimeImmutable;
use DateTimeZone;
use Fundrik\Core\Components\Shared\Domain\UtcDateTime;
use Fundrik\WordPress\Integration\ReadModels\Campaign;
use Fundrik\WordPress\Tests\Fixtures\WordPress\WpBlockEditorContext;
use Fundrik\WordPress\Tests\Fixtures\WordPress\Wpdb;
use Fundrik\WordPress\Tests\Fixtures\WordPress\WpError;
use Fundrik\WordPress\Tests\Fixtures\WordPress\WpListTable;
use Fundrik\WordPress\Tests\Fixtures\WordPress\WpPost;
use Fundrik\WordPress\Tests\Fixtures\WordPress\WpRestRequest;
use Fundrik\WordPress\Tests\Fixtures\WordPress\WpRestResponse;
use Fundrik\WordPress\Tests\Fixtures\WordPress\WpRestServer;
use Fundrik\WordPress\Tests\Fixtures\WordPress\WpScreen;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Override;

abstract class WordPressTestCase extends FundrikTestCase {

	use MockeryPHPUnitIntegration;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		if ( ! class_exists( 'WP_List_Table', false ) ) {
			class_alias( WpListTable::class, 'WP_List_Table' );
		}

		if ( ! class_exists( 'WP_Error', false ) ) {
			class_alias( WpError::class, 'WP_Error' );
		}

		if ( ! class_exists( 'WP_Post', false ) ) {
			class_alias( WpPost::class, 'WP_Post' );
		}

		if ( ! class_exists( 'WP_Block_Editor_Context', false ) ) {
			class_alias( WpBlockEditorContext::class, 'WP_Block_Editor_Context' );
		}

		if ( ! class_exists( 'WP_REST_Request', false ) ) {
			class_alias( WpRestRequest::class, 'WP_REST_Request' );
		}

		if ( ! class_exists( 'WP_REST_Response', false ) ) {
			class_alias( WpRestResponse::class, 'WP_REST_Response' );
		}

		if ( ! class_exists( 'WP_REST_Server', false ) ) {
			class_alias( WpRestServer::class, 'WP_REST_Server' );
		}

		if ( ! class_exists( 'WP_Screen', false ) ) {
			class_alias( WpScreen::class, 'WP_Screen' );
		}

		if ( ! class_exists( 'wpdb', false ) ) {
			class_alias( Wpdb::class, 'wpdb' );
		}

		Monkey\setUp();
		Monkey\Functions\stubEscapeFunctions();
		Monkey\Functions\stubTranslationFunctions();
	}

	#[Override]
	protected function tearDown(): void {

		Monkey\tearDown();

		parent::tearDown();
	}

	/**
	 * Uses native JSON encoding for the WordPress JSON helper.
	 */
	protected function stub_wp_json_encode(): void {

		Monkey\Functions\when( 'wp_json_encode' )->alias( 'json_encode' );
	}

	/**
	 * Uses deterministic number formatting for the WordPress locale helper.
	 */
	protected function stub_number_format_i18n(): void {

		Monkey\Functions\when( 'number_format_i18n' )->alias(
			static fn ( int|float $number, int $decimals = 0 ): string => number_format(
				(float) $number,
				$decimals,
				'.',
				',',
			),
		);
	}

	/**
	 * Uses a deterministic WordPress admin base URL.
	 */
	protected function stub_admin_url( string $base_url = 'https://example.test/wp-admin/' ): void {

		Monkey\Functions\when( 'admin_url' )->alias(
			static fn ( string $path = '' ): string => rtrim( $base_url, '/' ) . '/' . ltrim( $path, '/' ),
		);
	}

	/**
	 * Uses deterministic WordPress date and time formats in UTC.
	 */
	protected function stub_wp_date(
		string $date_format = 'Y-m-d',
		string $time_format = 'H:i:s',
	): void {

		Monkey\Functions\when( 'get_option' )->alias(
			static fn ( string $option ): string|false => match ( $option ) {
				'date_format' => $date_format,
				'time_format' => $time_format,
				default => false,
			},
		);
		Monkey\Functions\when( 'wp_date' )->alias(
			static fn ( string $format, int $timestamp ): string => gmdate( $format, $timestamp ),
		);
	}

	/**
	 * Returns a valid WordPress campaign read model with optional field overrides.
	 */
	protected function make_campaign_read_model(
		int $id = 42,
		string $title = 'Campaign',
		?string $permalink = 'https://example.test/campaign/42/',
		?int $featured_image_id = 99,
		bool $accepts_donations = true,
		string $currency_code = 'RUB',
		?int $target_amount = 10_000,
		int $collected_amount = 2_500,
		int $donations_count = 3,
		?UtcDateTime $created_at = null,
		?UtcDateTime $updated_at = null,
	): Campaign {

		return new Campaign(
			id: $id,
			title: $title,
			permalink: $permalink,
			featured_image_id: $featured_image_id,
			accepts_donations: $accepts_donations,
			currency_code: $currency_code,
			target_amount: $target_amount,
			collected_amount: $collected_amount,
			donations_count: $donations_count,
			created_at: $created_at ?? UtcDateTime::create(
				new DateTimeImmutable( '2026-03-21 10:00:00', new DateTimeZone( 'UTC' ) ),
			),
			updated_at: $updated_at,
		);
	}
}
