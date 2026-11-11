<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Database;

use Fundrik\WordPress\Integration\Database\WpdbDatabase;
use Fundrik\WordPress\Integration\Database\WpdbDatabaseException;
use Fundrik\WordPress\Integration\Database\WpdbDuplicateKeyException;
use Fundrik\WordPress\Integration\Database\WpdbRowNotFoundException;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use stdClass;
use wpdb;

#[CoversClass( WpdbDatabase::class )]
#[CoversClass( WpdbDatabaseException::class )]
#[CoversClass( WpdbDuplicateKeyException::class )]
#[CoversClass( WpdbRowNotFoundException::class )]
final class WpdbDatabaseTest extends WordPressTestCase {

	private wpdb&MockInterface $wpdb;
	private WpdbDatabase $database;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->wpdb = Mockery::mock( wpdb::class );
		$this->wpdb->last_error = '';
		$this->wpdb->prefix = 'wp_';

		// phpcs:ignore SlevomatCodingStandard.Variables.DisallowSuperGlobalVariable.DisallowedSuperGlobalVariable, WordPress.WP.GlobalVariablesOverride.Prohibited -- Supplies the WordPress database dependency.
		$GLOBALS['wpdb'] = $this->wpdb;

		$this->database = new WpdbDatabase();
	}

	#[Override]
	protected function tearDown(): void {

		// phpcs:ignore SlevomatCodingStandard.Variables.DisallowSuperGlobalVariable.DisallowedSuperGlobalVariable -- Removes the test database dependency.
		unset( $GLOBALS['wpdb'] );

		parent::tearDown();
	}

	#[Test]
	public function constructor_throws_when_global_wpdb_is_missing(): void {

		// phpcs:ignore SlevomatCodingStandard.Variables.DisallowSuperGlobalVariable.DisallowedSuperGlobalVariable -- Exercises the missing global dependency.
		unset( $GLOBALS['wpdb'] );

		$this->expectException( WpdbDatabaseException::class );
		$this->expectExceptionMessage( 'Global $wpdb must be an instance of wpdb. Given: null.' );

		new WpdbDatabase();
	}

	#[Test]
	public function constructor_throws_when_global_wpdb_has_invalid_type(): void {

		// phpcs:ignore SlevomatCodingStandard.Variables.DisallowSuperGlobalVariable.DisallowedSuperGlobalVariable, WordPress.WP.GlobalVariablesOverride.Prohibited -- Exercises an invalid global dependency.
		$GLOBALS['wpdb'] = new stdClass();

		$this->expectException( WpdbDatabaseException::class );
		$this->expectExceptionMessage( 'Global $wpdb must be an instance of wpdb. Given: stdClass.' );

		new WpdbDatabase();
	}

	#[Test]
	#[DataProvider( 'provide_ids_and_placeholders' )]
	public function get_by_id_uses_matching_id_placeholder( int|string $id, string $placeholder ): void {

		$row = [
			'id' => $id,
			'ratio' => 1.5,
			'title' => 'Campaign',
			'enabled' => true,
			'updated_at' => null,
		];

		$this->expect_prepared_query(
			"SELECT * FROM %i WHERE id = {$placeholder} LIMIT 1",
			[ 'wp_campaigns', $id ],
		);
		$this->wpdb
			->shouldReceive( 'get_row' )
			->once()
			->with( 'prepared_query', ARRAY_A )
			->andReturn( $row );

		self::assertSame( $row, $this->database->get_by_id( 'campaigns', $id ) );
	}

	#[Test]
	public function get_by_id_returns_null_when_row_is_missing(): void {

		$this->expect_prepared_query(
			'SELECT * FROM %i WHERE id = %d LIMIT 1',
			[ 'wp_campaigns', 7 ],
		);
		$this->wpdb
			->shouldReceive( 'get_row' )
			->once()
			->with( 'prepared_query', ARRAY_A )
			->andReturn( null );

		self::assertNull( $this->database->get_by_id( 'campaigns', 7 ) );
	}

	#[Test]
	public function get_by_id_throws_when_query_fails(): void {

		$this->expect_prepared_query(
			'SELECT * FROM %i WHERE id = %d LIMIT 1',
			[ 'wp_campaigns', 7 ],
		);
		$this->wpdb
			->shouldReceive( 'get_row' )
			->once()
			->with( 'prepared_query', ARRAY_A )
			->andReturn( null );
		$this->wpdb->last_error = 'Query failed';

		$this->expectException( WpdbDatabaseException::class );
		$this->expectExceptionMessage( 'Failed to fetch row "7" from table "wp_campaigns".' );

		$this->database->get_by_id( 'campaigns', 7 );
	}

	#[Test]
	public function get_by_id_throws_when_row_contains_non_scalar_value(): void {

		$this->expect_prepared_query(
			'SELECT * FROM %i WHERE id = %d LIMIT 1',
			[ 'wp_campaigns', 7 ],
		);
		$this->wpdb
			->shouldReceive( 'get_row' )
			->once()
			->with( 'prepared_query', ARRAY_A )
			->andReturn(
				[
					'id' => 7,
					'invalid' => [],
				],
			);

		$this->expectException( WpdbDatabaseException::class );
		$this->expectExceptionMessage( 'DB value must be scalar or null. Key: "invalid". Given: array.' );

		$this->database->get_by_id( 'campaigns', 7 );
	}

	#[Test]
	public function get_by_column_returns_matching_row(): void {

		$row = [
			'id' => 7,
			'payment_id' => 'pay_7',
		];

		$this->expect_prepared_query(
			'SELECT * FROM %i WHERE %i = %s LIMIT 1',
			[ 'wp_donations', 'payment_id', 'pay_7' ],
		);
		$this->wpdb
			->shouldReceive( 'get_row' )
			->once()
			->with( 'prepared_query', ARRAY_A )
			->andReturn( $row );

		self::assertSame(
			$row,
			$this->database->get_by_column( 'donations', 'payment_id', 'pay_7' ),
		);
	}

	#[Test]
	public function get_by_column_uses_is_null_comparison_and_returns_null_when_row_is_missing(): void {

		$this->expect_prepared_query(
			'SELECT * FROM %i WHERE %i IS NULL LIMIT 1',
			[ 'wp_donations', 'payment_id' ],
		);
		$this->wpdb
			->shouldReceive( 'get_row' )
			->once()
			->with( 'prepared_query', ARRAY_A )
			->andReturn( null );

		self::assertNull( $this->database->get_by_column( 'donations', 'payment_id', null ) );
	}

	#[Test]
	public function get_by_column_throws_when_query_fails(): void {

		$this->expect_prepared_query(
			'SELECT * FROM %i WHERE %i = %s LIMIT 1',
			[ 'wp_donations', 'payment_id', 'pay_7' ],
		);
		$this->wpdb
			->shouldReceive( 'get_row' )
			->once()
			->with( 'prepared_query', ARRAY_A )
			->andReturn( null );
		$this->wpdb->last_error = 'Query failed';

		$this->expectException( WpdbDatabaseException::class );
		$this->expectExceptionMessage(
			'Failed to fetch row from table "wp_donations" by column "payment_id" and value "pay_7".',
		);

		$this->database->get_by_column( 'donations', 'payment_id', 'pay_7' );
	}

	#[Test]
	public function get_all_returns_sanitized_rows(): void {

		$rows = [
			[
				'id' => 1,
				'title' => 'First',
			],
			[
				'id' => 2,
				'title' => 'Second',
			],
		];

		$this->expect_prepared_query( 'SELECT * FROM %i', [ 'wp_campaigns' ] );
		$this->wpdb
			->shouldReceive( 'get_results' )
			->once()
			->with( 'prepared_query', ARRAY_A )
			->andReturn( $rows );

		self::assertSame( $rows, $this->database->get_all( 'campaigns' ) );
	}

	#[Test]
	public function get_all_returns_empty_array_when_wpdb_does_not_return_rows(): void {

		$this->expect_prepared_query( 'SELECT * FROM %i', [ 'wp_campaigns' ] );
		$this->wpdb
			->shouldReceive( 'get_results' )
			->once()
			->with( 'prepared_query', ARRAY_A )
			->andReturn( null );

		self::assertSame( [], $this->database->get_all( 'campaigns' ) );
	}

	#[Test]
	public function get_all_throws_when_query_fails(): void {

		$this->expect_prepared_query( 'SELECT * FROM %i', [ 'wp_campaigns' ] );
		$this->wpdb
			->shouldReceive( 'get_results' )
			->once()
			->with( 'prepared_query', ARRAY_A )
			->andReturn( [] );
		$this->wpdb->last_error = 'Query failed';

		$this->expectException( WpdbDatabaseException::class );
		$this->expectExceptionMessage( 'Failed to fetch rows from table "wp_campaigns".' );

		$this->database->get_all( 'campaigns' );
	}

	#[Test]
	public function get_all_throws_when_any_row_contains_non_scalar_value(): void {

		$this->expect_prepared_query( 'SELECT * FROM %i', [ 'wp_campaigns' ] );
		$this->wpdb
			->shouldReceive( 'get_results' )
			->once()
			->with( 'prepared_query', ARRAY_A )
			->andReturn(
				[
					[ 'id' => 1 ],
					[
						'id' => 2,
						'invalid' => new stdClass(),
					],
				],
			);

		$this->expectException( WpdbDatabaseException::class );
		$this->expectExceptionMessage( 'DB value must be scalar or null. Key: "invalid". Given: stdClass.' );

		$this->database->get_all( 'campaigns' );
	}

	#[Test]
	public function get_all_by_ids_returns_rows_for_integer_and_string_ids(): void {

		$rows = [
			[ 'id' => 7 ],
			[ 'id' => 'campaign_uuid' ],
		];

		$this->expect_prepared_query(
			'SELECT * FROM %i WHERE id IN (%d, %s)',
			[ 'wp_campaigns', 7, 'campaign_uuid' ],
		);
		$this->wpdb
			->shouldReceive( 'get_results' )
			->once()
			->with( 'prepared_query', ARRAY_A )
			->andReturn( $rows );

		self::assertSame(
			$rows,
			$this->database->get_all_by_ids( 'campaigns', 7, 'campaign_uuid' ),
		);
	}

	#[Test]
	public function get_all_by_ids_returns_empty_array_without_query_when_no_ids_are_given(): void {

		$this->wpdb->shouldNotReceive( 'prepare' );

		self::assertSame( [], $this->database->get_all_by_ids( 'campaigns' ) );
	}

	#[Test]
	public function get_all_by_ids_throws_when_query_fails(): void {

		$this->expect_prepared_query(
			'SELECT * FROM %i WHERE id IN (%d)',
			[ 'wp_campaigns', 7 ],
		);
		$this->wpdb
			->shouldReceive( 'get_results' )
			->once()
			->with( 'prepared_query', ARRAY_A )
			->andReturn( [] );
		$this->wpdb->last_error = 'Query failed';

		$this->expectException( WpdbDatabaseException::class );
		$this->expectExceptionMessage( 'Failed to fetch rows from table "wp_campaigns" by IDs.' );

		$this->database->get_all_by_ids( 'campaigns', 7 );
	}

	#[Test]
	public function get_all_by_column_uses_float_placeholder_and_returns_rows(): void {

		$rows = [
			[
				'id' => 1,
				'ratio' => 1.5,
			],
		];

		$this->expect_prepared_query(
			'SELECT * FROM %i WHERE %i = %f',
			[ 'wp_campaigns', 'ratio', 1.5 ],
		);
		$this->wpdb
			->shouldReceive( 'get_results' )
			->once()
			->with( 'prepared_query', ARRAY_A )
			->andReturn( $rows );

		self::assertSame(
			$rows,
			$this->database->get_all_by_column( 'campaigns', 'ratio', 1.5 ),
		);
	}

	#[Test]
	public function get_all_by_column_returns_empty_array_when_wpdb_does_not_return_rows(): void {

		$this->expect_prepared_query(
			'SELECT * FROM %i WHERE %i = %d',
			[ 'wp_campaigns', 'enabled', 1 ],
		);
		$this->wpdb
			->shouldReceive( 'get_results' )
			->once()
			->with( 'prepared_query', ARRAY_A )
			->andReturn( null );

		self::assertSame( [], $this->database->get_all_by_column( 'campaigns', 'enabled', true ) );
	}

	#[Test]
	public function get_all_by_column_throws_when_query_fails(): void {

		$this->expect_prepared_query(
			'SELECT * FROM %i WHERE %i = %d',
			[ 'wp_campaigns', 'campaign_id', 7 ],
		);
		$this->wpdb
			->shouldReceive( 'get_results' )
			->once()
			->with( 'prepared_query', ARRAY_A )
			->andReturn( [] );
		$this->wpdb->last_error = 'Query failed';

		$this->expectException( WpdbDatabaseException::class );
		$this->expectExceptionMessage(
			'Failed to fetch rows from table "wp_campaigns" by column "campaign_id" and value "7".',
		);

		$this->database->get_all_by_column( 'campaigns', 'campaign_id', 7 );
	}

	#[Test]
	#[DataProvider( 'provide_invalid_pagination' )]
	public function paginate_throws_for_non_positive_arguments( int $page, int $per_page, string $message, ): void {

		$this->expectException( WpdbDatabaseException::class );
		$this->expectExceptionMessage( $message );

		$this->database->paginate( 'donations', $page, $per_page );
	}

	#[Test]
	public function paginate_returns_empty_result_without_row_query_when_table_is_empty(): void {

		$this->expect_prepared_query( 'SELECT COUNT(*) FROM %i', [ 'wp_donations' ] );
		$this->wpdb
			->shouldReceive( 'get_var' )
			->once()
			->with( 'prepared_query' )
			->andReturn( 0 );
		$this->wpdb->shouldNotReceive( 'get_results' );

		self::assertSame( [ [], 0 ], $this->database->paginate( 'donations', 1, 20 ) );
	}

	#[Test]
	public function paginate_clamps_page_to_last_page_and_returns_rows_with_total_count(): void {

		$rows = [
			[
				'id' => 21,
				'created_at' => '2026-01-01 10:00:00',
			],
		];

		$this->wpdb
			->shouldReceive( 'prepare' )
			->once()
			->with( 'SELECT COUNT(*) FROM %i', 'wp_donations' )
			->andReturn( 'count_query' );
		$this->wpdb
			->shouldReceive( 'get_var' )
			->once()
			->with( 'count_query' )
			->andReturn( 21 );
		$this->wpdb
			->shouldReceive( 'prepare' )
			->once()
			->with( 'SELECT * FROM %i ORDER BY created_at DESC, id DESC LIMIT %d OFFSET %d', 'wp_donations', 20, 20 )
			->andReturn( 'rows_query' );
		$this->wpdb
			->shouldReceive( 'get_results' )
			->once()
			->with( 'rows_query', ARRAY_A )
			->andReturn( $rows );

		self::assertSame( [ $rows, 21 ], $this->database->paginate( 'donations', 99, 20 ) );
	}

	#[Test]
	public function paginate_returns_empty_rows_when_wpdb_does_not_return_array(): void {

		$this->wpdb
			->shouldReceive( 'prepare' )
			->once()
			->with( 'SELECT COUNT(*) FROM %i', 'wp_donations' )
			->andReturn( 'count_query' );
		$this->wpdb
			->shouldReceive( 'get_var' )
			->once()
			->with( 'count_query' )
			->andReturn( 1 );
		$this->wpdb
			->shouldReceive( 'prepare' )
			->once()
			->with( 'SELECT * FROM %i ORDER BY created_at DESC, id DESC LIMIT %d OFFSET %d', 'wp_donations', 20, 0 )
			->andReturn( 'rows_query' );
		$this->wpdb
			->shouldReceive( 'get_results' )
			->once()
			->with( 'rows_query', ARRAY_A )
			->andReturn( null );

		self::assertSame( [ [], 1 ], $this->database->paginate( 'donations', 1, 20 ) );
	}

	#[Test]
	public function paginate_throws_when_count_query_fails(): void {

		$this->expect_prepared_query( 'SELECT COUNT(*) FROM %i', [ 'wp_donations' ] );
		$this->wpdb
			->shouldReceive( 'get_var' )
			->once()
			->with( 'prepared_query' )
			->andReturn( null );
		$this->wpdb->last_error = 'Count failed';

		$this->expectException( WpdbDatabaseException::class );
		$this->expectExceptionMessage( 'Failed to count rows in table "wp_donations".' );

		$this->database->paginate( 'donations', 1, 20 );
	}

	#[Test]
	public function paginate_throws_when_rows_query_fails(): void {

		$this->wpdb
			->shouldReceive( 'prepare' )
			->once()
			->with( 'SELECT COUNT(*) FROM %i', 'wp_donations' )
			->andReturn( 'count_query' );
		$this->wpdb
			->shouldReceive( 'get_var' )
			->once()
			->with( 'count_query' )
			->andReturnUsing(
				function (): int {

					$this->wpdb->last_error = '';

					return 1;
				},
			);
		$this->wpdb
			->shouldReceive( 'prepare' )
			->once()
			->with( 'SELECT * FROM %i ORDER BY created_at DESC, id DESC LIMIT %d OFFSET %d', 'wp_donations', 20, 0 )
			->andReturn( 'rows_query' );
		$this->wpdb
			->shouldReceive( 'get_results' )
			->once()
			->with( 'rows_query', ARRAY_A )
			->andReturnUsing(
				function (): array {

					$this->wpdb->last_error = 'Rows failed';

					return [];
				},
			);

		$this->expectException( WpdbDatabaseException::class );
		$this->expectExceptionMessage( 'Failed to fetch paginated rows from table "wp_donations".' );

		$this->database->paginate( 'donations', 1, 20 );
	}

	#[Test]
	#[DataProvider( 'provide_table_existence_results' )]
	public function table_exists_returns_whether_wpdb_found_table( mixed $result, bool $expected ): void {

		$this->expect_prepared_query( 'SHOW TABLES LIKE %s', [ 'wp_campaigns' ] );
		$this->wpdb
			->shouldReceive( 'get_var' )
			->once()
			->with( 'prepared_query' )
			->andReturn( $result );

		self::assertSame( $expected, $this->database->table_exists( 'campaigns' ) );
	}

	#[Test]
	public function table_exists_throws_when_query_fails(): void {

		$this->expect_prepared_query( 'SHOW TABLES LIKE %s', [ 'wp_campaigns' ] );
		$this->wpdb
			->shouldReceive( 'get_var' )
			->once()
			->with( 'prepared_query' )
			->andReturn( null );
		$this->wpdb->last_error = 'Query failed';

		$this->expectException( WpdbDatabaseException::class );
		$this->expectExceptionMessage( 'Failed to check table existence for table "wp_campaigns".' );

		$this->database->table_exists( 'campaigns' );
	}

	#[Test]
	#[DataProvider( 'provide_ids_placeholders_and_existence' )]
	public function exists_by_id_uses_matching_placeholder_and_returns_result(
		int|string $id,
		string $placeholder,
		mixed $result,
		bool $expected,
	): void {

		$this->expect_prepared_query(
			"SELECT 1 FROM %i WHERE id = {$placeholder} LIMIT 1",
			[ 'wp_campaigns', $id ],
		);
		$this->wpdb
			->shouldReceive( 'get_var' )
			->once()
			->with( 'prepared_query' )
			->andReturn( $result );

		self::assertSame( $expected, $this->database->exists_by_id( 'campaigns', $id ) );
	}

	#[Test]
	public function exists_by_id_throws_when_query_fails(): void {

		$this->expect_prepared_query(
			'SELECT 1 FROM %i WHERE id = %d LIMIT 1',
			[ 'wp_campaigns', 7 ],
		);
		$this->wpdb
			->shouldReceive( 'get_var' )
			->once()
			->with( 'prepared_query' )
			->andReturn( null );
		$this->wpdb->last_error = 'Query failed';

		$this->expectException( WpdbDatabaseException::class );
		$this->expectExceptionMessage( 'Failed to check row existence for table "wp_campaigns" and ID "7".' );

		$this->database->exists_by_id( 'campaigns', 7 );
	}

	#[Test]
	#[DataProvider( 'provide_column_filters' )]
	public function exists_by_column_uses_matching_filter(
		int|float|string|bool|null $value,
		string $where_sql,
		array $where_args,
		mixed $result,
		bool $expected,
	): void {

		$this->expect_prepared_query(
			"SELECT 1 FROM %i {$where_sql} LIMIT 1",
			[ 'wp_campaigns', ...$where_args ],
		);
		$this->wpdb
			->shouldReceive( 'get_var' )
			->once()
			->with( 'prepared_query' )
			->andReturn( $result );

		self::assertSame(
			$expected,
			$this->database->exists_by_column( 'campaigns', 'filter_value', $value ),
		);
	}

	#[Test]
	public function exists_by_column_throws_when_query_fails(): void {

		$this->expect_prepared_query(
			'SELECT 1 FROM %i WHERE %i = %s LIMIT 1',
			[ 'wp_campaigns', 'status', 'active' ],
		);
		$this->wpdb
			->shouldReceive( 'get_var' )
			->once()
			->with( 'prepared_query' )
			->andReturn( null );
		$this->wpdb->last_error = 'Query failed';

		$this->expectException( WpdbDatabaseException::class );
		$this->expectExceptionMessage(
			'Failed to check row existence for table "wp_campaigns", column "status", value "active".',
		);

		$this->database->exists_by_column( 'campaigns', 'status', 'active' );
	}

	#[Test]
	public function insert_passes_qualified_table_and_data_to_wpdb(): void {

		$data = [
			'id' => 7,
			'title' => 'Campaign',
		];

		$this->wpdb
			->shouldReceive( 'insert' )
			->once()
			->with( 'wp_campaigns', $data )
			->andReturn( 1 );

		$this->database->insert( 'campaigns', $data );
	}

	#[Test]
	public function insert_throws_duplicate_key_exception_for_duplicate_error(): void {

		$data = [ 'id' => 7 ];

		$this->wpdb
			->shouldReceive( 'insert' )
			->once()
			->with( 'wp_campaigns', $data )
			->andReturnUsing(
				function (): int {

					$this->wpdb->last_error = 'Duplicate entry "7" for key "PRIMARY"';

					return 1;
				},
			);

		$this->expectException( WpdbDuplicateKeyException::class );
		$this->expectExceptionMessage( 'Cannot insert row into table "wp_campaigns": duplicate key.' );

		$this->database->insert( 'campaigns', $data );
	}

	#[Test]
	#[DataProvider( 'provide_wpdb_failures' )]
	public function insert_throws_database_exception_for_other_failures(
		int|false $result,
		string $last_error,
	): void {

		$data = [ 'id' => 7 ];

		$this->wpdb
			->shouldReceive( 'insert' )
			->once()
			->with( 'wp_campaigns', $data )
			->andReturnUsing(
				function () use ( $result, $last_error ): int|false {

					$this->wpdb->last_error = $last_error;

					return $result;
				},
			);

		$this->expectException( WpdbDatabaseException::class );
		$this->expectExceptionMessage( 'Failed to insert row into table "wp_campaigns".' );

		$this->database->insert( 'campaigns', $data );
	}

	#[Test]
	public function update_returns_affected_row_count(): void {

		$data = [ 'title' => 'Updated' ];
		$where = [ 'id' => 7 ];

		$this->wpdb
			->shouldReceive( 'update' )
			->once()
			->with( 'wp_campaigns', $data, $where )
			->andReturn( 2 );

		self::assertSame( 2, $this->database->update( 'campaigns', $data, $where ) );
	}

	#[Test]
	#[DataProvider( 'provide_wpdb_failures' )]
	public function update_throws_when_wpdb_reports_failure( int|false $result, string $last_error, ): void {

		$data = [ 'title' => 'Updated' ];
		$where = [ 'id' => 7 ];

		$this->wpdb
			->shouldReceive( 'update' )
			->once()
			->with( 'wp_campaigns', $data, $where )
			->andReturnUsing(
				function () use ( $result, $last_error ): int|false {

					$this->wpdb->last_error = $last_error;

					return $result;
				},
			);

		$this->expectException( WpdbDatabaseException::class );
		$this->expectExceptionMessage( 'Failed to update rows in table "wp_campaigns".' );

		$this->database->update( 'campaigns', $data, $where );
	}

	#[Test]
	public function apply_numeric_deltas_executes_atomic_update(): void {

		$this->expect_prepared_query(
			'UPDATE %i SET %i = %i + %d, %i = %i + %d WHERE id = %d',
			[
				'wp_campaigns',
				'collected_amount',
				'collected_amount',
				500,
				'donations_count',
				'donations_count',
				1,
				7,
			],
		);
		$this->wpdb
			->shouldReceive( 'query' )
			->once()
			->with( 'prepared_query' )
			->andReturn( 1 );

		$this->database->apply_numeric_deltas(
			'campaigns',
			7,
			[
				'collected_amount' => 500,
				'donations_count' => 1,
			],
		);
	}

	#[Test]
	public function apply_numeric_deltas_uses_string_placeholder_for_string_id(): void {

		$this->expect_prepared_query(
			'UPDATE %i SET %i = %i + %d WHERE id = %s',
			[ 'wp_campaigns', 'collected_amount', 'collected_amount', 500, 'campaign_uuid' ],
		);
		$this->wpdb
			->shouldReceive( 'query' )
			->once()
			->with( 'prepared_query' )
			->andReturn( 1 );

		$this->database->apply_numeric_deltas(
			'campaigns',
			'campaign_uuid',
			[ 'collected_amount' => 500 ],
		);
	}

	#[Test]
	public function apply_numeric_deltas_throws_for_empty_delta_list(): void {

		$this->expectException( WpdbDatabaseException::class );
		$this->expectExceptionMessage( 'Numeric deltas must be non-empty. Given: empty array.' );

		$this->database->apply_numeric_deltas( 'campaigns', 7, [] );
	}

	#[Test]
	public function apply_numeric_deltas_throws_for_zero_delta(): void {

		$this->expectException( WpdbDatabaseException::class );
		$this->expectExceptionMessage( 'Numeric delta for column "collected_amount" must be non-zero. Given: 0.' );

		$this->database->apply_numeric_deltas(
			'campaigns',
			7,
			[ 'collected_amount' => 0 ],
		);
	}

	#[Test]
	public function apply_numeric_deltas_throws_when_row_is_missing(): void {

		$this->expect_prepared_query(
			'UPDATE %i SET %i = %i + %d WHERE id = %d',
			[ 'wp_campaigns', 'collected_amount', 'collected_amount', 500, 7 ],
		);
		$this->wpdb
			->shouldReceive( 'query' )
			->once()
			->with( 'prepared_query' )
			->andReturn( 0 );

		$this->expectException( WpdbRowNotFoundException::class );
		$this->expectExceptionMessage(
			'Cannot apply numeric deltas to row "7" in table "wp_campaigns": row not found.',
		);

		$this->database->apply_numeric_deltas(
			'campaigns',
			7,
			[ 'collected_amount' => 500 ],
		);
	}

	#[Test]
	#[DataProvider( 'provide_wpdb_failures' )]
	public function apply_numeric_deltas_throws_when_wpdb_reports_failure(
		int|false $result,
		string $last_error,
	): void {

		$this->expect_prepared_query(
			'UPDATE %i SET %i = %i + %d WHERE id = %d',
			[ 'wp_campaigns', 'collected_amount', 'collected_amount', 500, 7 ],
		);
		$this->wpdb
			->shouldReceive( 'query' )
			->once()
			->with( 'prepared_query' )
			->andReturnUsing(
				function () use ( $result, $last_error ): int|false {

					$this->wpdb->last_error = $last_error;

					return $result;
				},
			);

		$this->expectException( WpdbDatabaseException::class );
		$this->expectExceptionMessage( 'Failed to apply numeric deltas to row "7" in table "wp_campaigns".' );

		$this->database->apply_numeric_deltas(
			'campaigns',
			7,
			[ 'collected_amount' => 500 ],
		);
	}

	#[Test]
	public function delete_passes_qualified_table_and_id_to_wpdb(): void {

		$this->wpdb
			->shouldReceive( 'delete' )
			->once()
			->with( 'wp_campaigns', [ 'id' => 7 ] )
			->andReturn( 1 );

		$this->database->delete( 'campaigns', 7 );
	}

	#[Test]
	public function delete_throws_when_row_is_missing(): void {

		$this->wpdb
			->shouldReceive( 'delete' )
			->once()
			->with( 'wp_campaigns', [ 'id' => 7 ] )
			->andReturn( 0 );

		$this->expectException( WpdbRowNotFoundException::class );
		$this->expectExceptionMessage( 'Cannot delete row "7" from table "wp_campaigns": row not found.' );

		$this->database->delete( 'campaigns', 7 );
	}

	#[Test]
	#[DataProvider( 'provide_wpdb_failures' )]
	public function delete_throws_when_wpdb_reports_failure( int|false $result, string $last_error, ): void {

		$this->wpdb
			->shouldReceive( 'delete' )
			->once()
			->with( 'wp_campaigns', [ 'id' => 7 ] )
			->andReturnUsing(
				function () use ( $result, $last_error ): int|false {

					$this->wpdb->last_error = $last_error;

					return $result;
				},
			);

		$this->expectException( WpdbDatabaseException::class );
		$this->expectExceptionMessage( 'Failed to delete row "7" from table "wp_campaigns".' );

		$this->database->delete( 'campaigns', 7 );
	}

	#[Test]
	public function query_accepts_zero_affected_rows(): void {

		$this->wpdb
			->shouldReceive( 'query' )
			->once()
			->with( 'UPDATE wp_campaigns SET title = title' )
			->andReturn( 0 );

		$this->database->query( 'UPDATE wp_campaigns SET title = title' );
	}

	#[Test]
	#[DataProvider( 'provide_wpdb_failures' )]
	public function query_throws_when_wpdb_reports_failure( int|false $result, string $last_error, ): void {

		$this->wpdb
			->shouldReceive( 'query' )
			->once()
			->with( 'SELECT 1' )
			->andReturnUsing(
				function () use ( $result, $last_error ): int|false {

					$this->wpdb->last_error = $last_error;

					return $result;
				},
			);

		$this->expectException( WpdbDatabaseException::class );
		$this->expectExceptionMessage( 'Failed to execute database query.' );

		$this->database->query( 'SELECT 1' );
	}

	#[Test]
	public function query_with_args_prepares_and_executes_query(): void {

		$sql = 'UPDATE %i SET title = %s WHERE id = %d';
		$args = [ 'wp_campaigns', 'Updated', 7 ];

		$this->expect_prepared_query( $sql, $args );
		$this->wpdb
			->shouldReceive( 'query' )
			->once()
			->with( 'prepared_query' )
			->andReturn( 1 );

		$this->database->query_with_args( $sql, ...$args );
	}

	#[Test]
	public function query_with_args_throws_when_prepare_does_not_return_string(): void {

		$sql = 'SELECT * FROM %i WHERE id = %d';

		$this->wpdb
			->shouldReceive( 'prepare' )
			->once()
			->with( $sql, 'wp_campaigns', 7 )
			->andReturn( null );

		$this->expectException( WpdbDatabaseException::class );
		$this->expectExceptionMessage( 'Prepared query must be a string. Given: null.' );

		$this->database->query_with_args( $sql, 'wp_campaigns', 7 );
	}

	#[Test]
	public function get_charset_collate_returns_wpdb_value(): void {

		$this->wpdb
			->shouldReceive( 'get_charset_collate' )
			->once()
			->andReturn( 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci' );

		self::assertSame(
			'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
			$this->database->get_charset_collate(),
		);
	}

	#[Test]
	public function get_charset_collate_throws_for_empty_value(): void {

		$this->wpdb
			->shouldReceive( 'get_charset_collate' )
			->once()
			->andReturn( '' );

		$this->expectException( WpdbDatabaseException::class );
		$this->expectExceptionMessage( 'Database charset and collation must be non-empty. Given: empty string.' );

		$this->database->get_charset_collate();
	}

	#[Test]
	#[DataProvider( 'provide_table_names_and_prefixes' )]
	public function qualify_table_name_applies_prefix_once( string $prefix, string $table, string $expected, ): void {

		$this->wpdb->prefix = $prefix;

		self::assertSame( $expected, $this->database->qualify_table_name( $table ) );
	}

	/**
	 * Provides IDs and their wpdb placeholders.
	 *
	 * @return iterable<string, array{int|string, string}> Test cases.
	 */
	public static function provide_ids_and_placeholders(): iterable {

		yield 'integer ID' => [ 7, '%d' ];
		yield 'string ID' => [ 'campaign_uuid', '%s' ];
	}

	/**
	 * Provides invalid pagination arguments.
	 *
	 * @return iterable<string, array{int, int, string}> Test cases.
	 */
	public static function provide_invalid_pagination(): iterable {

		yield 'zero page' => [ 0, 20, 'Page must be a positive integer. Given: 0.' ];
		yield 'negative page' => [ -1, 20, 'Page must be a positive integer. Given: -1.' ];
		yield 'zero items per page' => [ 1, 0, 'Items per page must be a positive integer. Given: 0.' ];
		yield 'negative items per page' => [ 1, -1, 'Items per page must be a positive integer. Given: -1.' ];
	}

	/**
	 * Provides table lookup results.
	 *
	 * @return iterable<string, array{mixed, bool}> Test cases.
	 */
	public static function provide_table_existence_results(): iterable {

		yield 'table exists' => [ 'wp_campaigns', true ];
		yield 'table is missing' => [ null, false ];
	}

	/**
	 * Provides IDs, placeholders, and lookup results.
	 *
	 * @return iterable<string, array{int|string, string, mixed, bool}> Test cases.
	 */
	public static function provide_ids_placeholders_and_existence(): iterable {

		yield 'integer ID exists' => [ 7, '%d', 1, true ];
		yield 'string ID is missing' => [ 'campaign_uuid', '%s', null, false ];
	}

	/**
	 * Provides supported column filter values.
	 *
	 * @return iterable<string, array{int|float|string|bool|null, string, list<int|float|string>, mixed, bool}> Test cases.
	 */
	public static function provide_column_filters(): iterable {

		yield 'string' => [ 'active', 'WHERE %i = %s', [ 'filter_value', 'active' ], 1, true ];
		yield 'integer' => [ 7, 'WHERE %i = %d', [ 'filter_value', 7 ], 1, true ];
		yield 'float' => [ 1.5, 'WHERE %i = %f', [ 'filter_value', 1.5 ], 1, true ];
		yield 'true' => [ true, 'WHERE %i = %d', [ 'filter_value', 1 ], 1, true ];
		yield 'false' => [ false, 'WHERE %i = %d', [ 'filter_value', 0 ], 1, true ];
		yield 'null' => [ null, 'WHERE %i IS NULL', [ 'filter_value' ], null, false ];
	}

	/**
	 * Provides wpdb failure signals.
	 *
	 * @return iterable<string, array{int|false, string}> Test cases.
	 */
	public static function provide_wpdb_failures(): iterable {

		yield 'false result' => [ false, '' ];
		yield 'last error' => [ 1, 'Database failed' ];
	}

	/**
	 * Provides table names and prefixes.
	 *
	 * @return iterable<string, array{string, string, string}> Test cases.
	 */
	public static function provide_table_names_and_prefixes(): iterable {

		yield 'unprefixed table' => [ 'wp_', 'campaigns', 'wp_campaigns' ];
		yield 'already prefixed table' => [ 'wp_', 'wp_campaigns', 'wp_campaigns' ];
		yield 'empty prefix' => [ '', 'campaigns', 'campaigns' ];
	}

	/**
	 * Expects wpdb to prepare a query.
	 *
	 * @param string $sql SQL template.
	 * @param list<int|float|string|bool|null> $args Query arguments.
	 */
	private function expect_prepared_query( string $sql, array $args ): void {

		$this->wpdb
			->shouldReceive( 'prepare' )
			->once()
			->with( $sql, ...$args )
			->andReturn( 'prepared_query' );
	}
}
