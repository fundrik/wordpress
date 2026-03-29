<?php

declare(strict_types=1);

namespace Fundrik\WordPress\IntegrationTests;

use Fundrik\WordPress\Integration\PostTypes\Configs\CampaignPostTypeConfig;
use Fundrik\WordPress\Integration\RestApi\Routes\DonationsRestRoute;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use WP_REST_Request;
use WP_REST_Response;
use wpdb;

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
#[CoversNothing]
final class CreateDonationViaRestApiTest extends TestCase {

	private const string CAMPAIGNS_TABLE_SUFFIX = 'fundrik_campaigns';
	private const string DONATIONS_TABLE_SUFFIX = 'fundrik_donations';
	private const string DONATIONS_REQUEST_PATH = '/' . DonationsRestRoute::ROUTE_NAMESPACE . DonationsRestRoute::ROUTE_PATH;

	private int $post_id = 0;

	private array $donation_ids = [];

	protected function tearDown(): void {

		global $wpdb;

		if ( $this->post_id > 0 ) {
			wp_delete_post( $this->post_id, true );
		}

		if ( $wpdb instanceof wpdb ) {

			$campaigns_table = $wpdb->prefix . self::CAMPAIGNS_TABLE_SUFFIX;
			$donations_table = $wpdb->prefix . self::DONATIONS_TABLE_SUFFIX;

			if ( $this->post_id > 0 ) {
				$wpdb->delete(
					$campaigns_table,
					[ 'id' => $this->post_id ],
					[ '%d' ],
				);
			}

			foreach ( $this->donation_ids as $donation_id ) {
				$wpdb->delete(
					$donations_table,
					[ 'id' => $donation_id ],
					[ '%s' ],
				);
			}
		}

		parent::tearDown();
	}

	#[Test]
	public function creating_donation_via_rest_api_inserts_row_into_fundrik_donations_table(): void {

		global $wpdb;

		self::assertInstanceOf( wpdb::class, $wpdb );

		$campaigns_table = $wpdb->prefix . self::CAMPAIGNS_TABLE_SUFFIX;
		$donations_table = $wpdb->prefix . self::DONATIONS_TABLE_SUFFIX;

		$this->assert_table_exists( $wpdb, $campaigns_table );
		$this->assert_table_exists( $wpdb, $donations_table );

		$post_id = wp_insert_post(
			[
				'post_type' => CampaignPostTypeConfig::ID,
				'post_status' => 'publish',
				'post_title' => 'Donation API Campaign',
			],
			true,
		);

		self::assertIsInt( $post_id );
		self::assertGreaterThan( 0, $post_id );

		$this->post_id = $post_id;

		$wpdb->delete(
			$campaigns_table,
			[ 'id' => $post_id ],
			[ '%d' ],
		);

		$inserted_campaign = $wpdb->insert(
			$campaigns_table,
			[
				'id' => $post_id,
				'version' => 1,
				'title' => 'Donation API Campaign',
				'accepts_donations' => 1,
				'currency_code' => 'RUB',
				'target_amount' => null,
				'created_at' => '2026-03-21 10:00:00',
				'updated_at' => null,
			],
			[ '%d', '%d', '%s', '%d', '%s', '%d', '%s', '%s' ],
		);

		self::assertSame( 1, $inserted_campaign );

		$request = new WP_REST_Request( 'POST', self::DONATIONS_REQUEST_PATH );
		$request->set_header( 'Content-Type', 'application/json' );

		$donation_id = $this->generate_donation_id();

		$request->set_body(
			(string) wp_json_encode(
				[
					'donation_id' => $donation_id,
					'campaign_id' => $post_id,
					'amount' => 2_500,
				],
			),
		);

		$response = rest_do_request( $request );

		self::assertInstanceOf( WP_REST_Response::class, $response );
		self::assertSame( 201, $response->get_status() );

		$data = $response->get_data();

		self::assertIsArray( $data );
		self::assertIsString( $data['id'] ?? null );
		self::assertSame( $post_id, $data['campaign_id'] ?? null );
		self::assertSame( 2_500, $data['amount'] ?? null );
		self::assertSame( 'pending', $data['status'] ?? null );

		$donation_id = (string) $data['id'];
		$this->donation_ids[] = $donation_id;

		$duplicate_request = new WP_REST_Request( 'POST', self::DONATIONS_REQUEST_PATH );
		$duplicate_request->set_header( 'Content-Type', 'application/json' );
		$duplicate_request->set_body(
			(string) wp_json_encode(
				[
					'donation_id' => $donation_id,
					'campaign_id' => $post_id,
					'amount' => 2_500,
				],
			),
		);

		$duplicate_response = rest_do_request( $duplicate_request );

		self::assertInstanceOf( WP_REST_Response::class, $duplicate_response );
		self::assertSame( 201, $duplicate_response->get_status() );

		$duplicate_data = $duplicate_response->get_data();

		self::assertIsArray( $duplicate_data );
		self::assertSame( $donation_id, $duplicate_data['id'] ?? null );
		self::assertSame( $post_id, $duplicate_data['campaign_id'] ?? null );
		self::assertSame( 2_500, $duplicate_data['amount'] ?? null );

		$query = $wpdb->prepare( 'SELECT * FROM %i WHERE id = %s LIMIT 1', $donations_table, $donation_id );

		self::assertIsString( $query );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$row = $wpdb->get_row( $query, ARRAY_A );

		self::assertIsArray( $row );
		self::assertSame( $donation_id, $row['id'] );
		self::assertSame( 1, (int) $row['version'] );
		self::assertSame( $post_id, (int) $row['campaign_id'] );
		self::assertSame( 2_500, (int) $row['amount'] );
		self::assertSame( 'RUB', (string) $row['currency_code'] );
		self::assertSame( 'pending', (string) $row['status'] );

		$count_query = $wpdb->prepare(
			'SELECT COUNT(*) FROM %i WHERE campaign_id = %d AND amount = %d',
			$donations_table,
			$post_id,
			2_500,
		);

		self::assertIsString( $count_query );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$count = $wpdb->get_var( $count_query );

		self::assertSame( '1', (string) $count );
	}

	#[Test]
	public function creating_donation_via_rest_api_returns_validation_error_for_invalid_amount(): void {

		$request = $this->create_donation_request(
			[
				'donation_id' => $this->generate_donation_id(),
				'campaign_id' => 1,
				'amount' => 0,
			],
		);

		$response = rest_do_request( $request );

		self::assertInstanceOf( WP_REST_Response::class, $response );
		self::assertSame( 400, $response->get_status() );

		$data = $response->get_data();

		self::assertIsArray( $data );
		self::assertSame( 'rest_invalid_param', $data['code'] ?? null );
	}

	#[Test]
	public function creating_donation_via_rest_api_returns_not_found_for_missing_campaign(): void {

		$request = $this->create_donation_request(
			[
				'donation_id' => $this->generate_donation_id(),
				'campaign_id' => 987_654_321,
				'amount' => 1_000,
			],
		);

		$response = rest_do_request( $request );

		self::assertInstanceOf( WP_REST_Response::class, $response );
		self::assertSame( 404, $response->get_status() );

		$data = $response->get_data();

		self::assertIsArray( $data );
		self::assertSame( 'fundrik_campaign_not_found', $data['code'] ?? null );
	}

	#[Test]
	public function creating_donation_via_rest_api_returns_conflict_for_closed_campaign(): void {

		global $wpdb;

		self::assertInstanceOf( wpdb::class, $wpdb );

		$campaigns_table = $wpdb->prefix . self::CAMPAIGNS_TABLE_SUFFIX;
		$donations_table = $wpdb->prefix . self::DONATIONS_TABLE_SUFFIX;

		$this->assert_table_exists( $wpdb, $campaigns_table );
		$this->assert_table_exists( $wpdb, $donations_table );

		$post_id = wp_insert_post(
			[
				'post_type' => CampaignPostTypeConfig::ID,
				'post_status' => 'publish',
				'post_title' => 'Donation API Closed Campaign',
			],
			true,
		);

		self::assertIsInt( $post_id );
		self::assertGreaterThan( 0, $post_id );

		$this->post_id = $post_id;

		$wpdb->delete(
			$campaigns_table,
			[ 'id' => $post_id ],
			[ '%d' ],
		);

		$inserted_campaign = $wpdb->insert(
			$campaigns_table,
			[
				'id' => $post_id,
				'version' => 1,
				'title' => 'Donation API Closed Campaign',
				'accepts_donations' => 0,
				'currency_code' => 'RUB',
				'target_amount' => null,
				'created_at' => '2026-03-21 10:00:00',
				'updated_at' => null,
			],
			[ '%d', '%d', '%s', '%d', '%s', '%d', '%s', '%s' ],
		);

		self::assertSame( 1, $inserted_campaign );

		$request = $this->create_donation_request(
			[
				'donation_id' => $this->generate_donation_id(),
				'campaign_id' => $post_id,
				'amount' => 1_000,
			],
		);

		$response = rest_do_request( $request );

		self::assertInstanceOf( WP_REST_Response::class, $response );
		self::assertSame( 409, $response->get_status() );

		$data = $response->get_data();

		self::assertIsArray( $data );
		self::assertSame( 'fundrik_campaign_cannot_receive_donations', $data['code'] ?? null );

		$count_query = $wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE campaign_id = %d', $donations_table, $post_id );

		self::assertIsString( $count_query );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$count = $wpdb->get_var( $count_query );

		self::assertSame( '0', (string) $count );
	}

	private function assert_table_exists( wpdb $wpdb, string $table_name ): void {

		$table_exists = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$table_name,
			),
		);

		self::assertSame( $table_name, $table_exists );
	}

	/**
	 * Creates a donation REST request from the normalized payload.
	 *
	 * @param array{donation_id: string, campaign_id: int, amount: int} $payload Request payload.
	 */
	private function create_donation_request( array $payload ): WP_REST_Request {

		$request = new WP_REST_Request( 'POST', self::DONATIONS_REQUEST_PATH );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( (string) wp_json_encode( $payload ) );

		return $request;
	}

	private function generate_donation_id(): string {

		$key = wp_generate_uuid4();

		if ( is_string( $key ) && $key !== '' ) {
			return $key;
		}

		return Uuid::uuid4()->toString();
	}
}


