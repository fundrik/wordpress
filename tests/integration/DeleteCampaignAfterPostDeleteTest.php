<?php

declare(strict_types=1);

namespace Fundrik\WordPress\IntegrationTests;

use Fundrik\WordPress\Integration\PostTypes\Configs\CampaignPostTypeConfig;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use wpdb;

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
final class DeleteCampaignAfterPostDeleteTest extends TestCase {

	private const string TABLE_SUFFIX = 'fundrik_campaigns';

	private int $post_id = 0;

	protected function tearDown(): void {

		global $wpdb;

		if ( $this->post_id > 0 ) {
			wp_delete_post( $this->post_id, true );
		}

		if ( $wpdb instanceof wpdb && $this->post_id > 0 ) {

			$table_name = $wpdb->prefix . self::TABLE_SUFFIX;
			$wpdb->delete(
				$table_name,
				[ 'id' => $this->post_id ],
				[ '%d' ],
			);
		}

		parent::tearDown();
	}

	#[Test]
	public function deleting_campaign_post_deletes_campaign_row_in_fundrik_table(): void {

		global $wpdb;

		self::assertInstanceOf( wpdb::class, $wpdb );

		$table_name = $wpdb->prefix . self::TABLE_SUFFIX;
		$table_exists = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$table_name,
			),
		);

		self::assertSame( $table_name, $table_exists );

		$post_id = wp_insert_post(
			[
				'post_type' => CampaignPostTypeConfig::ID,
				'post_status' => 'draft',
				'post_title' => 'Integration Campaign',
			],
			true,
		);

		self::assertIsInt( $post_id );
		self::assertGreaterThan( 0, $post_id );

		$this->post_id = $post_id;

		$wpdb->delete(
			$table_name,
			[ 'id' => $post_id ],
			[ '%d' ],
		);

		$insert_result = $wpdb->insert(
			$table_name,
			[
				'id' => $post_id,
				'version' => 1,
				'title' => 'Integration Campaign',
				'accepts_donations' => 1,
				'currency_code' => 'RUB',
				'target_amount' => null,
				'created_at' => '2026-03-21 10:00:00',
				'updated_at' => null,
			],
			[ '%d', '%d', '%s', '%d', '%s', '%d', '%s', '%s' ],
		);

		self::assertSame( 1, $insert_result );

		$existing_rows = $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT COUNT(*) FROM `{$table_name}` WHERE id = %d",
				$post_id,
			),
		);

		self::assertSame( '1', (string) $existing_rows );

		wp_delete_post( $post_id, true );
		$this->post_id = 0;

		$remaining_rows = $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT COUNT(*) FROM `{$table_name}` WHERE id = %d",
				$post_id,
			),
		);

		self::assertSame( '0', (string) $remaining_rows );
	}

	#[Test]
	public function deleting_non_campaign_post_keeps_campaign_row_in_fundrik_table(): void {

		global $wpdb;

		self::assertInstanceOf( wpdb::class, $wpdb );

		$table_name = $wpdb->prefix . self::TABLE_SUFFIX;
		$table_exists = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$table_name,
			),
		);

		self::assertSame( $table_name, $table_exists );

		$post_id = wp_insert_post(
			[
				'post_type' => 'post',
				'post_status' => 'draft',
				'post_title' => 'Regular integration post',
			],
			true,
		);

		self::assertIsInt( $post_id );
		self::assertGreaterThan( 0, $post_id );

		$this->post_id = $post_id;

		$wpdb->delete(
			$table_name,
			[ 'id' => $post_id ],
			[ '%d' ],
		);

		$insert_result = $wpdb->insert(
			$table_name,
			[
				'id' => $post_id,
				'version' => 1,
				'title' => 'Row for regular post',
				'accepts_donations' => 1,
				'currency_code' => 'RUB',
				'target_amount' => null,
				'created_at' => '2026-03-21 10:00:00',
				'updated_at' => null,
			],
			[ '%d', '%d', '%s', '%d', '%s', '%d', '%s', '%s' ],
		);

		self::assertSame( 1, $insert_result );

		wp_delete_post( $post_id, true );

		$remaining_rows = $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT COUNT(*) FROM `{$table_name}` WHERE id = %d",
				$post_id,
			),
		);

		self::assertSame( '1', (string) $remaining_rows );
	}
}

