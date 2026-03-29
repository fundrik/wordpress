<?php

declare(strict_types=1);

namespace Fundrik\WordPress\IntegrationTests;

use Fundrik\WordPress\Integration\PostTypes\Configs\CampaignPostTypeConfig;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WP_Post;
use WP_REST_Request;
use WP_REST_Response;
use wpdb;

#[CoversNothing]
final class SyncCampaignAfterPostSaveTest extends TestCase {

	private const string TABLE_SUFFIX = 'fundrik_campaigns';

	private int $post_id = 0;

	protected function tearDown(): void {

		global $wpdb;

		wp_set_current_user( 0 );

		if ( $this->post_id > 0 ) {
			wp_delete_post( $this->post_id, true );
		}

		if ( $wpdb instanceof wpdb && $this->post_id > 0 ) {

			$table_name = $wpdb->prefix . self::TABLE_SUFFIX;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->delete(
				$table_name,
				[ 'id' => $this->post_id ],
				[ '%d' ],
			);
		}

		parent::tearDown();
	}

	#[Test]
	public function creating_campaign_post_creates_campaign_row(): void {

		global $wpdb;

		self::assertInstanceOf( wpdb::class, $wpdb );

		$table_name = $wpdb->prefix . self::TABLE_SUFFIX;
		$this->assert_table_exists( $wpdb, $table_name );

		$post_id = wp_insert_post(
			[
				'post_type' => CampaignPostTypeConfig::ID,
				'post_status' => 'draft',
				'post_title' => 'Integration Campaign Create',
			],
			true,
		);

		self::assertIsInt( $post_id );
		self::assertGreaterThan( 0, $post_id );

		$this->post_id = $post_id;

		update_post_meta( $post_id, CampaignPostTypeConfig::META_ACCEPTS_DONATIONS, true );
		update_post_meta( $post_id, CampaignPostTypeConfig::META_HAS_TARGET, true );
		update_post_meta( $post_id, CampaignPostTypeConfig::META_TARGET_AMOUNT, 700 );
		update_post_meta( $post_id, CampaignPostTypeConfig::META_TARGET_CURRENCY, 'RUB' );

		$post = get_post( $post_id );
		self::assertInstanceOf( WP_Post::class, $post );

		$this->dispatch_rest_after_insert( $post, 1, true );

		$row = $this->get_campaign_row( $wpdb, $table_name, $post_id );

		self::assertIsArray( $row );
		self::assertSame( $post_id, (int) $row['id'] );
		self::assertSame( 1, (int) $row['version'] );
		self::assertSame( 'Integration Campaign Create', (string) $row['title'] );
		self::assertSame( 1, (int) $row['accepts_donations'] );
		self::assertSame( 'RUB', (string) $row['currency_code'] );
		self::assertSame( 700, (int) $row['target_amount'] );
		self::assertNotEmpty( $row['created_at'] );
		self::assertNull( $row['updated_at'] );
	}

	#[Test]
	public function updating_campaign_post_updates_campaign_row(): void {

		global $wpdb;

		self::assertInstanceOf( wpdb::class, $wpdb );

		$table_name = $wpdb->prefix . self::TABLE_SUFFIX;
		$this->assert_table_exists( $wpdb, $table_name );

		$post_id = wp_insert_post(
			[
				'post_type' => CampaignPostTypeConfig::ID,
				'post_status' => 'draft',
				'post_title' => 'Integration Campaign Initial',
			],
			true,
		);

		self::assertIsInt( $post_id );
		self::assertGreaterThan( 0, $post_id );

		$this->post_id = $post_id;

		update_post_meta( $post_id, CampaignPostTypeConfig::META_ACCEPTS_DONATIONS, true );
		update_post_meta( $post_id, CampaignPostTypeConfig::META_HAS_TARGET, false );
		update_post_meta( $post_id, CampaignPostTypeConfig::META_TARGET_AMOUNT, 0 );
		update_post_meta( $post_id, CampaignPostTypeConfig::META_TARGET_CURRENCY, 'RUB' );

		$initial_post = get_post( $post_id );
		self::assertInstanceOf( WP_Post::class, $initial_post );

		$this->dispatch_rest_after_insert( $initial_post, 1, true );

		$updated_post_id = wp_update_post(
			[
				'ID' => $post_id,
				'post_status' => 'publish',
				'post_title' => 'Integration Campaign Updated',
			],
			true,
		);

		self::assertSame( $post_id, $updated_post_id );

		update_post_meta( $post_id, CampaignPostTypeConfig::META_ACCEPTS_DONATIONS, false );
		update_post_meta( $post_id, CampaignPostTypeConfig::META_HAS_TARGET, true );
		update_post_meta( $post_id, CampaignPostTypeConfig::META_TARGET_AMOUNT, 1_500 );
		update_post_meta( $post_id, CampaignPostTypeConfig::META_TARGET_CURRENCY, 'USD' );

		$updated_post = get_post( $post_id );
		self::assertInstanceOf( WP_Post::class, $updated_post );

		$this->dispatch_rest_after_insert( $updated_post, 2, false );

		$row = $this->get_campaign_row( $wpdb, $table_name, $post_id );

		self::assertIsArray( $row );
		self::assertSame( $post_id, (int) $row['id'] );
		self::assertSame( 2, (int) $row['version'] );
		self::assertSame( 'Integration Campaign Updated', (string) $row['title'] );
		self::assertSame( 0, (int) $row['accepts_donations'] );
		self::assertSame( 'USD', (string) $row['currency_code'] );
		self::assertSame( 1_500, (int) $row['target_amount'] );
		self::assertNotEmpty( $row['created_at'] );
		self::assertNotNull( $row['updated_at'] );
	}

	#[Test]
	public function updating_campaign_post_with_stale_version_returns_conflict_and_keeps_campaign_row_unchanged(): void {

		global $wpdb;

		self::assertInstanceOf( wpdb::class, $wpdb );

		$table_name = $wpdb->prefix . self::TABLE_SUFFIX;
		$this->assert_table_exists( $wpdb, $table_name );

		$post_id = wp_insert_post(
			[
				'post_type' => CampaignPostTypeConfig::ID,
				'post_status' => 'publish',
				'post_title' => 'Integration Campaign v1',
			],
			true,
		);

		self::assertIsInt( $post_id );
		self::assertGreaterThan( 0, $post_id );

		$this->post_id = $post_id;

		update_post_meta( $post_id, CampaignPostTypeConfig::META_ACCEPTS_DONATIONS, true );
		update_post_meta( $post_id, CampaignPostTypeConfig::META_HAS_TARGET, false );
		update_post_meta( $post_id, CampaignPostTypeConfig::META_TARGET_AMOUNT, 0 );
		update_post_meta( $post_id, CampaignPostTypeConfig::META_TARGET_CURRENCY, 'RUB' );

		$post_v1 = get_post( $post_id );
		self::assertInstanceOf( WP_Post::class, $post_v1 );

		$this->dispatch_rest_after_insert( $post_v1, 1, true );

		$updated_post_id = wp_update_post(
			[
				'ID' => $post_id,
				'post_status' => 'publish',
				'post_title' => 'Integration Campaign v2',
			],
			true,
		);

		self::assertSame( $post_id, $updated_post_id );

		update_post_meta( $post_id, CampaignPostTypeConfig::META_ACCEPTS_DONATIONS, false );
		update_post_meta( $post_id, CampaignPostTypeConfig::META_HAS_TARGET, true );
		update_post_meta( $post_id, CampaignPostTypeConfig::META_TARGET_AMOUNT, 1_200 );
		update_post_meta( $post_id, CampaignPostTypeConfig::META_TARGET_CURRENCY, 'USD' );

		$post_v2 = get_post( $post_id );
		self::assertInstanceOf( WP_Post::class, $post_v2 );

		$this->dispatch_rest_after_insert( $post_v2, 2, false );
		$this->authenticate_as_admin();

		$request = new WP_REST_Request( 'POST', '/wp/v2/' . CampaignPostTypeConfig::ID . '/' . $post_id );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			(string) wp_json_encode(
				[
					'id' => $post_id,
					'title' => 'Rejected stale update',
					'status' => 'publish',
					'meta' => [
						CampaignPostTypeConfig::ENTITY_VERSION_FIELD_NAME => 1,
						CampaignPostTypeConfig::META_ACCEPTS_DONATIONS => true,
						CampaignPostTypeConfig::META_HAS_TARGET => true,
						CampaignPostTypeConfig::META_TARGET_AMOUNT => 100,
						CampaignPostTypeConfig::META_TARGET_CURRENCY => 'EUR',
					],
				],
			),
		);

		$response = rest_do_request( $request );

		self::assertInstanceOf( WP_REST_Response::class, $response );
		self::assertSame( 409, $response->get_status() );

		$data = $response->get_data();

		self::assertIsArray( $data );
		self::assertSame( 'fundrik_campaign_version_mismatch', $data['code'] ?? null );

		$row = $this->get_campaign_row( $wpdb, $table_name, $post_id );

		self::assertIsArray( $row );
		self::assertSame( 2, (int) $row['version'] );
		self::assertSame( 'Integration Campaign v2', (string) $row['title'] );
		self::assertSame( 0, (int) $row['accepts_donations'] );
		self::assertSame( 'USD', (string) $row['currency_code'] );
		self::assertSame( 1_200, (int) $row['target_amount'] );
	}

	#[Test]
	public function getting_campaign_via_rest_returns_initial_entity_version_when_campaign_row_is_missing(): void {

		global $wpdb;

		self::assertInstanceOf( wpdb::class, $wpdb );

		$table_name = $wpdb->prefix . self::TABLE_SUFFIX;
		$this->assert_table_exists( $wpdb, $table_name );

		$post_id = wp_insert_post(
			[
				'post_type' => CampaignPostTypeConfig::ID,
				'post_status' => 'publish',
				'post_title' => 'Integration Campaign Missing Row',
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

		$this->authenticate_as_admin();

		$request = new WP_REST_Request( 'GET', '/wp/v2/' . CampaignPostTypeConfig::ID . '/' . $post_id );
		$request->set_query_params( [ 'context' => 'edit' ] );

		$response = rest_do_request( $request );

		self::assertInstanceOf( WP_REST_Response::class, $response );
		self::assertSame( 200, $response->get_status() );

		$data = $response->get_data();

		self::assertIsArray( $data );
		self::assertIsArray( $data['meta'] ?? null );
		self::assertSame(
			1,
			(int) ( $data['meta'][ CampaignPostTypeConfig::ENTITY_VERSION_FIELD_NAME ] ?? 0 ),
		);
	}

	#[Test]
	public function getting_campaign_via_rest_returns_persisted_entity_version_from_campaign_row(): void {

		global $wpdb;

		self::assertInstanceOf( wpdb::class, $wpdb );

		$table_name = $wpdb->prefix . self::TABLE_SUFFIX;
		$this->assert_table_exists( $wpdb, $table_name );

		$post_id = wp_insert_post(
			[
				'post_type' => CampaignPostTypeConfig::ID,
				'post_status' => 'publish',
				'post_title' => 'Integration Campaign Persisted Row',
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

		$inserted_campaign = $wpdb->insert(
			$table_name,
			[
				'id' => $post_id,
				'version' => 7,
				'title' => 'Integration Campaign Persisted Row',
				'accepts_donations' => 1,
				'currency_code' => 'RUB',
				'target_amount' => null,
				'created_at' => '2026-03-21 10:00:00',
				'updated_at' => null,
			],
			[ '%d', '%d', '%s', '%d', '%s', '%d', '%s', '%s' ],
		);

		self::assertSame( 1, $inserted_campaign );

		$this->authenticate_as_admin();

		$request = new WP_REST_Request( 'GET', '/wp/v2/' . CampaignPostTypeConfig::ID . '/' . $post_id );
		$request->set_query_params( [ 'context' => 'edit' ] );

		$response = rest_do_request( $request );

		self::assertInstanceOf( WP_REST_Response::class, $response );
		self::assertSame( 200, $response->get_status() );

		$data = $response->get_data();

		self::assertIsArray( $data );
		self::assertIsArray( $data['meta'] ?? null );
		self::assertSame(
			7,
			(int) ( $data['meta'][ CampaignPostTypeConfig::ENTITY_VERSION_FIELD_NAME ] ?? 0 ),
		);
	}

	private function assert_table_exists( wpdb $wpdb, string $table_name ): void {

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$table_name,
			),
		);

		self::assertSame( $table_name, $table_exists );
	}

	private function get_campaign_row( wpdb $wpdb, string $table_name, int $post_id ): ?array {

		$query = $wpdb->prepare( 'SELECT * FROM %i WHERE id = %d LIMIT 1', $table_name, $post_id );

		if ( ! is_string( $query ) ) {
			self::fail( 'Failed to prepare row lookup query.' );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$row = $wpdb->get_row( $query, ARRAY_A );

		return is_array( $row ) ? $row : null;
	}

	private function dispatch_rest_after_insert( WP_Post $post, int $version, bool $creating ): void {

		$request = new WP_REST_Request( 'POST', '/wp/v2/' . CampaignPostTypeConfig::ID . '/' . $post->ID );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			(string) wp_json_encode(
				[
					'meta' => [
						CampaignPostTypeConfig::ENTITY_VERSION_FIELD_NAME => $version,
					],
				],
			),
		);

		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		do_action( 'rest_after_insert_' . CampaignPostTypeConfig::ID, $post, $request, $creating );
	}

	private function authenticate_as_admin(): void {

		$admin_ids = get_users(
			[
				'role' => 'administrator',
				'number' => 1,
				'fields' => 'ids',
			],
		);

		if ( ! is_array( $admin_ids ) || ! isset( $admin_ids[0] ) ) {
			self::fail( 'Administrator user was not found for integration REST request.' );
		}

		wp_set_current_user( (int) $admin_ids[0] );
	}
}


