<?php

declare(strict_types=1);

namespace Fundrik\WordPress\IntegrationTests;

use Fundrik\Core\Components\Donations\Application\Events\DonationSucceededEvent;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusPort;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\WordPress\Integration\PostTypes\Configs\CampaignPostTypeConfig;
use Fundrik\WordPress\Integration\RestApi\RestRouteDefinitions;
use Fundrik\WordPress\Integration\RestApi\Routes\DonationsRestRoute;
use Fundrik\WordPress\Kernel\Container\RuntimeContainer;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use WP_Post;
use WP_REST_Request;
use WP_REST_Response;
use wpdb;

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
#[CoversNothing]
final class CampaignSummaryAfterDonationSucceededEventTest extends TestCase {

	private const string CAMPAIGNS_TABLE_SUFFIX = 'fundrik_campaigns';
	private const string DONATIONS_TABLE_SUFFIX = 'fundrik_donations';

	private int $post_id = 0;

	private string $donation_id = '';

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

			if ( $this->donation_id !== '' ) {
				$wpdb->delete(
					$donations_table,
					[ 'id' => $this->donation_id ],
					[ '%s' ],
				);
			}
		}

		parent::tearDown();
	}

	#[Test]
	public function publishing_donation_succeeded_event_updates_campaign_summary_fields(): void {

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
				'post_title' => 'Campaign Summary Integration',
			],
			true,
		);

		self::assertIsInt( $post_id );
		self::assertGreaterThan( 0, $post_id );

		$this->post_id = $post_id;

		update_post_meta( $post_id, CampaignPostTypeConfig::META_ACCEPTS_DONATIONS, true );
		update_post_meta( $post_id, CampaignPostTypeConfig::META_HAS_TARGET, true );
		update_post_meta( $post_id, CampaignPostTypeConfig::META_TARGET_AMOUNT, 1_000 );
		update_post_meta( $post_id, CampaignPostTypeConfig::META_TARGET_CURRENCY, 'RUB' );

		$post = get_post( $post_id );
		self::assertInstanceOf( WP_Post::class, $post );

		$this->dispatch_rest_after_insert( $post, 1, true );

		$initial_row = $this->get_campaign_row( $wpdb, $campaigns_table, $post_id );

		self::assertIsArray( $initial_row );
		self::assertSame( 0, (int) $initial_row['collected_amount'] );
		self::assertSame( 0, (int) $initial_row['donations_count'] );

		$request = new WP_REST_Request( 'POST', $this->get_donations_request_path() );
		$request->set_header( 'Content-Type', 'application/json' );

		$this->donation_id = Uuid::uuid4()->toString();

		$request->set_body(
			(string) wp_json_encode(
				[
					'donation_id' => $this->donation_id,
					'campaign_id' => $post_id,
					'amount' => 2_500,
				],
			),
		);

		$response = rest_do_request( $request );

		self::assertInstanceOf( WP_REST_Response::class, $response );
		self::assertSame( 201, $response->get_status() );

		$donation_bus = RuntimeContainer::get()->make( ApplicationEventBusPort::class );
		self::assertInstanceOf( ApplicationEventBusPort::class, $donation_bus );

		$donation_bus->publish(
			new DonationSucceededEvent( EntityId::create( $this->donation_id ) ),
		);

		$row = $this->get_campaign_row( $wpdb, $campaigns_table, $post_id );

		self::assertIsArray( $row );
		self::assertSame( 2_500, (int) $row['collected_amount'] );
		self::assertSame( 1, (int) $row['donations_count'] );
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

	private function get_campaign_row( wpdb $wpdb, string $table_name, int $post_id ): ?array {

		$query = $wpdb->prepare( 'SELECT * FROM %i WHERE id = %d LIMIT 1', $table_name, $post_id );

		if ( ! is_string( $query ) ) {
			self::fail( 'Failed to prepare campaign row lookup query.' );
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

	private function get_donations_request_path(): string {

		return RestRouteDefinitions::get_request_path( DonationsRestRoute::class );
	}
}
