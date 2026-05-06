<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration;

use Fundrik\WordPress\Infrastructure\Ports\Database\DatabasePort;
use Fundrik\WordPress\Integration\Database\WpdbDatabase;
use Fundrik\WordPress\Integration\Database\WpdbDatabaseException;
use Fundrik\WordPress\Integration\Database\WpdbRowNotFoundException;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use stdClass;
use wpdb;

#[CoversClass( WpdbDatabase::class )]
final class WpdbDatabaseTest extends MockeryTestCase {

	private wpdb&MockInterface $wpdb;

	private WpdbDatabase $db;

	protected function setUp(): void {

		parent::setUp();

		$this->wpdb = Mockery::mock( 'wpdb' );
		$this->wpdb->last_error = '';
		$this->wpdb->prefix = 'wp_';

		// phpcs:ignore SlevomatCodingStandard.Variables.DisallowSuperGlobalVariable.DisallowedSuperGlobalVariable, WordPress.WP.GlobalVariablesOverride.Prohibited
		$GLOBALS['wpdb'] = $this->wpdb;

		$this->db = new WpdbDatabase();
	}

	#[Test]
	public function it_implements_database_interface(): void {

		self::assertInstanceOf( DatabasePort::class, $this->db );
	}

	#[Test]
	public function constructor_throws_when_global_wpdb_is_missing(): void {

		// phpcs:ignore SlevomatCodingStandard.Variables.DisallowSuperGlobalVariable.DisallowedSuperGlobalVariable
		unset( $GLOBALS['wpdb'] );

		$this->expectException( WpdbDatabaseException::class );
		$this->expectExceptionMessage( 'Global $wpdb must be an instance of wpdb.' );

		new WpdbDatabase();
	}

	#[Test]
	public function constructor_throws_when_global_wpdb_has_invalid_type(): void {

		// phpcs:ignore SlevomatCodingStandard.Variables.DisallowSuperGlobalVariable.DisallowedSuperGlobalVariable, WordPress.WP.GlobalVariablesOverride.Prohibited
		$GLOBALS['wpdb'] = new stdClass();

		$this->expectException( WpdbDatabaseException::class );
		$this->expectExceptionMessage( 'Global $wpdb must be an instance of wpdb.' );

		new WpdbDatabase();
	}

	// ---------------------------------------------------------------------
	// get_by_id()
	// ---------------------------------------------------------------------

	#[Test]
	public function get_by_id_prepares_query_with_int_placeholder_and_returns_sanitized_row(): void {

		$table = 'wp_table';
		$id = 7;

		$sql = 'SELECT * FROM %i WHERE id = %d LIMIT 1';
		$query = 'prepared_query';
		$result = [
			'id' => $id,
			'title' => 'Test',
		];

		$this->wpdb
			->shouldReceive( 'prepare' )
			->once()
			->with( $sql, $table, $id )
			->andReturn( $query );

		$this->wpdb
			->shouldReceive( 'get_row' )
			->once()
			->with( $query, ARRAY_A )
			->andReturn( $result );

		self::assertSame( $result, $this->db->get_by_id( $table, $id ) );
	}

	#[Test]
	public function get_by_id_prepares_query_with_string_placeholder_and_returns_row(): void {

		$table = 'wp_table';
		$id = 'abc';

		$sql = 'SELECT * FROM %i WHERE id = %s LIMIT 1';
		$query = 'prepared_query';
		$result = [
			'id' => $id,
			'title' => 'Test',
		];

		$this->wpdb
			->shouldReceive( 'prepare' )
			->once()
			->with( $sql, $table, $id )
			->andReturn( $query );

		$this->wpdb
			->shouldReceive( 'get_row' )
			->once()
			->with( $query, ARRAY_A )
			->andReturn( $result );

		self::assertSame( $result, $this->db->get_by_id( $table, $id ) );
	}

	#[Test]
	public function get_by_id_returns_null_when_row_is_not_found(): void {

		$table = 'wp_table';
		$id = 7;

		$sql = 'SELECT * FROM %i WHERE id = %d LIMIT 1';
		$query = 'prepared_query';

		$this->wpdb
			->shouldReceive( 'prepare' )
			->once()
			->with( $sql, $table, $id )
			->andReturn( $query );

		$this->wpdb
			->shouldReceive( 'get_row' )
			->once()
			->with( $query, ARRAY_A )
			->andReturn( null );

		self::assertNull( $this->db->get_by_id( $table, $id ) );
	}

	#[Test]
	public function get_by_id_throws_when_query_fails(): void {

		$table = 'wp_table';
		$id = 7;

		$sql = 'SELECT * FROM %i WHERE id = %d LIMIT 1';
		$query = 'prepared_query';

		$this->wpdb
			->shouldReceive( 'prepare' )
			->once()
			->with( $sql, $table, $id )
			->andReturn( $query );

		$this->wpdb
			->shouldReceive( 'get_row' )
			->once()
			->with( $query, ARRAY_A )
			->andReturn( null );

		$this->wpdb->last_error = 'Boom';

		$this->expectException( WpdbDatabaseException::class );
		$this->expectExceptionMessage( 'Failed to fetch row "7" from table "wp_table".' );

		$this->db->get_by_id( $table, $id );
	}

	#[Test]
	public function get_by_id_throws_when_row_contains_non_scalar_value(): void {

		$table = 'wp_table';
		$id = 7;

		$sql = 'SELECT * FROM %i WHERE id = %d LIMIT 1';
		$query = 'prepared_query';

		$this->wpdb
			->shouldReceive( 'prepare' )
			->once()
			->with( $sql, $table, $id )
			->andReturn( $query );

		$this->wpdb
			->shouldReceive( 'get_row' )
			->once()
			->with( $query, ARRAY_A )
			->andReturn(
				[
					'id' => 7,
					'bad' => [ 'nope' ],
				],
			);

		$this->expectException( WpdbDatabaseException::class );
		$this->expectExceptionMessage( 'DB value must be scalar or null. Key: "bad". Given: array.' );

		$this->db->get_by_id( $table, $id );
	}

	// ---------------------------------------------------------------------
	// get_all()
	// ---------------------------------------------------------------------

	#[Test]
	public function get_all_returns_rows_as_list_and_sanitizes_each_row(): void {

		$table = 'wp_table';

		$sql = 'SELECT * FROM %i';
		$query = 'prepared_query';

		$results = [
			[
				'id' => 2,
				'title' => 'B',
			],
			[
				'id' => 1,
				'title' => 'A',
			],
		];

		$this->wpdb
			->shouldReceive( 'prepare' )
			->once()
			->with( $sql, $table )
			->andReturn( $query );

		$this->wpdb
			->shouldReceive( 'get_results' )
			->once()
			->with( $query, ARRAY_A )
			->andReturn( $results );

		self::assertSame( $results, $this->db->get_all( $table ) );
	}

	#[Test]
	public function get_all_returns_empty_array_when_results_is_not_array(): void {

		$table = 'wp_table';

		$sql = 'SELECT * FROM %i';
		$query = 'prepared_query';

		$this->wpdb
			->shouldReceive( 'prepare' )
			->once()
			->with( $sql, $table )
			->andReturn( $query );

		$this->wpdb
			->shouldReceive( 'get_results' )
			->once()
			->with( $query, ARRAY_A )
			->andReturn( null );

		self::assertSame( [], $this->db->get_all( $table ) );
	}

	#[Test]
	public function get_all_throws_when_query_fails(): void {

		$table = 'wp_table';

		$sql = 'SELECT * FROM %i';
		$query = 'prepared_query';

		$this->wpdb
			->shouldReceive( 'prepare' )
			->once()
			->with( $sql, $table )
			->andReturn( $query );

		$this->wpdb
			->shouldReceive( 'get_results' )
			->once()
			->with( $query, ARRAY_A )
			->andReturn( [] );

		$this->wpdb->last_error = 'Boom';

		$this->expectException( WpdbDatabaseException::class );
		$this->expectExceptionMessage( 'Failed to fetch rows from table "wp_table".' );

		$this->db->get_all( $table );
	}

	#[Test]
	public function get_all_throws_when_any_row_contains_non_scalar_value(): void {

		$table = 'wp_table';

		$sql = 'SELECT * FROM %i';
		$query = 'prepared_query';

		$results = [
			[
				'id' => 1,
				'title' => 'A',
			],
			[
				'id' => 2,
				'bad' => [ 'nope' ],
			],
		];

		$this->wpdb
			->shouldReceive( 'prepare' )
			->once()
			->with( $sql, $table )
			->andReturn( $query );

		$this->wpdb
			->shouldReceive( 'get_results' )
			->once()
			->with( $query, ARRAY_A )
			->andReturn( $results );

		$this->expectException( WpdbDatabaseException::class );
		$this->expectExceptionMessage( 'DB value must be scalar or null. Key: "bad". Given: array.' );

		$this->db->get_all( $table );
	}

	// ---------------------------------------------------------------------
	// table_exists()
	// ---------------------------------------------------------------------

	#[Test]
	public function table_exists_returns_true_when_table_exists_and_false_when_not(): void {

		$table = 'table';
		$sql = 'SHOW TABLES LIKE %s';
		$query = 'prepared_query';

		$this->wpdb
			->shouldReceive( 'prepare' )
			->twice()
			->with( $sql, 'wp_table' )
			->andReturn( $query );

		$this->wpdb
			->shouldReceive( 'get_var' )
			->once()
			->with( $query )
			->andReturn( 'wp_table' );

		self::assertTrue( $this->db->table_exists( $table ) );

		$this->wpdb
			->shouldReceive( 'get_var' )
			->once()
			->with( $query )
			->andReturn( null );

		self::assertFalse( $this->db->table_exists( $table ) );
	}

	#[Test]
	public function table_exists_throws_when_query_fails(): void {

		$table = 'table';
		$sql = 'SHOW TABLES LIKE %s';
		$query = 'prepared_query';

		$this->wpdb
			->shouldReceive( 'prepare' )
			->once()
			->with( $sql, 'wp_table' )
			->andReturn( $query );

		$this->wpdb
			->shouldReceive( 'get_var' )
			->once()
			->with( $query )
			->andReturn( null );

		$this->wpdb->last_error = 'Boom';

		$this->expectException( WpdbDatabaseException::class );
		$this->expectExceptionMessage( 'Failed to check table existence for table "wp_table".' );

		$this->db->table_exists( $table );
	}

	// ---------------------------------------------------------------------
	// get_all_by_column()
	// ---------------------------------------------------------------------

	#[Test]
	public function get_all_by_column_returns_rows_as_list_and_sanitizes_each_row(): void {

		$table = 'wp_table';
		$column = 'campaign_id';
		$value = 77;

		$sql = 'SELECT * FROM %i WHERE %i = %d';
		$query = 'prepared_query';

		$results = [
			[
				'id' => 2,
				'campaign_id' => 77,
			],
			[
				'id' => 1,
				'campaign_id' => 77,
			],
		];

		$this->wpdb
			->shouldReceive( 'prepare' )
			->once()
			->with( $sql, $table, $column, $value )
			->andReturn( $query );

		$this->wpdb
			->shouldReceive( 'get_results' )
			->once()
			->with( $query, ARRAY_A )
			->andReturn( $results );

		self::assertSame( $results, $this->db->get_all_by_column( $table, $column, $value ) );
	}

	#[Test]
	public function get_all_by_column_uses_string_placeholder_when_value_is_string(): void {

		$table = 'wp_table';
		$column = 'email';
		$value = 'a@b.com';

		$sql = 'SELECT * FROM %i WHERE %i = %s';
		$query = 'prepared_query';

		$results = [
			[
				'id' => 1,
				'email' => 'a@b.com',
			],
		];

		$this->wpdb
			->shouldReceive( 'prepare' )
			->once()
			->with( $sql, $table, $column, $value )
			->andReturn( $query );

		$this->wpdb
			->shouldReceive( 'get_results' )
			->once()
			->with( $query, ARRAY_A )
			->andReturn( $results );

		self::assertSame( $results, $this->db->get_all_by_column( $table, $column, $value ) );
	}

	#[Test]
	public function get_all_by_column_uses_float_placeholder_when_value_is_float(): void {

		$table = 'wp_table';
		$column = 'amount';
		$value = 12.5;

		$sql = 'SELECT * FROM %i WHERE %i = %f';
		$query = 'prepared_query';

		$results = [
			[
				'id' => 1,
				'amount' => 12.5,
			],
		];

		$this->wpdb
			->shouldReceive( 'prepare' )
			->once()
			->with( $sql, $table, $column, $value )
			->andReturn( $query );

		$this->wpdb
			->shouldReceive( 'get_results' )
			->once()
			->with( $query, ARRAY_A )
			->andReturn( $results );

		self::assertSame( $results, $this->db->get_all_by_column( $table, $column, $value ) );
	}

	#[Test]
	public function get_all_by_column_uses_is_null_comparison_when_value_is_null(): void {

		$table = 'wp_table';
		$column = 'updated_at';
		$value = null;

		$sql = 'SELECT * FROM %i WHERE %i IS NULL';
		$query = 'prepared_query';

		$results = [
			[
				'id' => 1,
				'updated_at' => null,
			],
		];

		$this->wpdb
			->shouldReceive( 'prepare' )
			->once()
			->with( $sql, $table, $column )
			->andReturn( $query );

		$this->wpdb
			->shouldReceive( 'get_results' )
			->once()
			->with( $query, ARRAY_A )
			->andReturn( $results );

		self::assertSame( $results, $this->db->get_all_by_column( $table, $column, $value ) );
	}

	#[Test]
	public function get_all_by_column_returns_empty_array_when_results_is_not_array(): void {

		$table = 'wp_table';
		$column = 'campaign_id';
		$value = 77;

		$sql = 'SELECT * FROM %i WHERE %i = %d';
		$query = 'prepared_query';

		$this->wpdb
			->shouldReceive( 'prepare' )
			->once()
			->with( $sql, $table, $column, $value )
			->andReturn( $query );

		$this->wpdb
			->shouldReceive( 'get_results' )
			->once()
			->with( $query, ARRAY_A )
			->andReturn( null );

		self::assertSame( [], $this->db->get_all_by_column( $table, $column, $value ) );
	}

	#[Test]
	public function get_all_by_column_throws_when_query_fails(): void {

		$table = 'wp_table';
		$column = 'campaign_id';
		$value = 77;

		$sql = 'SELECT * FROM %i WHERE %i = %d';
		$query = 'prepared_query';

		$this->wpdb
			->shouldReceive( 'prepare' )
			->once()
			->with( $sql, $table, $column, $value )
			->andReturn( $query );

		$this->wpdb
			->shouldReceive( 'get_results' )
			->once()
			->with( $query, ARRAY_A )
			->andReturn( [] );

		$this->wpdb->last_error = 'Boom';

		$this->expectException( WpdbDatabaseException::class );
		$this->expectExceptionMessage(
			'Failed to fetch rows from table "wp_table" by column "campaign_id" and value "77".',
		);

		$this->db->get_all_by_column( $table, $column, $value );
	}

	#[Test]
	public function get_all_by_column_throws_when_any_row_contains_non_scalar_value(): void {

		$table = 'wp_table';
		$column = 'campaign_id';
		$value = 77;

		$sql = 'SELECT * FROM %i WHERE %i = %d';
		$query = 'prepared_query';

		$results = [
			[
				'id' => 1,
				'campaign_id' => 77,
			],
			[
				'id' => 2,
				'bad' => [ 'nope' ],
			],
		];

		$this->wpdb
			->shouldReceive( 'prepare' )
			->once()
			->with( $sql, $table, $column, $value )
			->andReturn( $query );

		$this->wpdb
			->shouldReceive( 'get_results' )
			->once()
			->with( $query, ARRAY_A )
			->andReturn( $results );

		$this->expectException( WpdbDatabaseException::class );
		$this->expectExceptionMessage( 'DB value must be scalar or null. Key: "bad". Given: array.' );

		$this->db->get_all_by_column( $table, $column, $value );
	}

	#[Test]
	public function exists_by_id_returns_true_when_row_exists_and_false_when_not(): void {

		$table = 'wp_table';
		$id = 7;

		$sql = 'SELECT 1 FROM %i WHERE id = %d LIMIT 1';
		$query = 'prepared_query';

		$this->wpdb
			->shouldReceive( 'prepare' )
			->twice()
			->with( $sql, $table, $id )
			->andReturn( $query );

		$this->wpdb
			->shouldReceive( 'get_var' )
			->once()
			->with( $query )
			->andReturn( 1 );

		self::assertTrue( $this->db->exists_by_id( $table, $id ) );

		$this->wpdb
			->shouldReceive( 'get_var' )
			->once()
			->with( $query )
			->andReturn( null );

		self::assertFalse( $this->db->exists_by_id( $table, $id ) );
	}

	#[Test]
	public function exists_by_id_uses_string_placeholder_when_id_is_string(): void {

		$table = 'wp_table';
		$id = 'abc';

		$sql = 'SELECT 1 FROM %i WHERE id = %s LIMIT 1';
		$query = 'prepared_query';

		$this->wpdb
			->shouldReceive( 'prepare' )
			->once()
			->with( $sql, $table, $id )
			->andReturn( $query );

		$this->wpdb
			->shouldReceive( 'get_var' )
			->once()
			->with( $query )
			->andReturn( 1 );

		self::assertTrue( $this->db->exists_by_id( $table, $id ) );
	}

	#[Test]
	public function exists_by_id_throws_when_query_fails(): void {

		$table = 'wp_table';
		$id = 7;

		$sql = 'SELECT 1 FROM %i WHERE id = %d LIMIT 1';
		$query = 'prepared_query';

		$this->wpdb
			->shouldReceive( 'prepare' )
			->once()
			->with( $sql, $table, $id )
			->andReturn( $query );

		$this->wpdb
			->shouldReceive( 'get_var' )
			->once()
			->with( $query )
			->andReturn( null );

		$this->wpdb->last_error = 'Boom';

		$this->expectException( WpdbDatabaseException::class );
		$this->expectExceptionMessage( 'Failed to check row existence for table "wp_table" and ID "7".' );

		$this->db->exists_by_id( $table, $id );
	}

	// ---------------------------------------------------------------------
	// exists_by_column()
	// ---------------------------------------------------------------------

	#[Test]
	public function exists_by_column_returns_true_when_row_exists_and_false_when_not(): void {

		$table = 'wp_table';
		$column = 'email';
		$value = 'a@b.com';

		$sql = 'SELECT 1 FROM %i WHERE %i = %s LIMIT 1';
		$query = 'prepared_query';

		$this->wpdb
			->shouldReceive( 'prepare' )
			->twice()
			->with( $sql, $table, $column, $value )
			->andReturn( $query );

		$this->wpdb
			->shouldReceive( 'get_var' )
			->once()
			->with( $query )
			->andReturn( 1 );

		self::assertTrue( $this->db->exists_by_column( $table, $column, $value ) );

		$this->wpdb
			->shouldReceive( 'get_var' )
			->once()
			->with( $query )
			->andReturn( null );

		self::assertFalse( $this->db->exists_by_column( $table, $column, $value ) );
	}

	#[Test]
	public function exists_by_column_uses_int_placeholder_when_value_is_int(): void {

		$table = 'wp_table';
		$column = 'user_id';
		$value = 123;

		$sql = 'SELECT 1 FROM %i WHERE %i = %d LIMIT 1';
		$query = 'prepared_query';

		$this->wpdb
			->shouldReceive( 'prepare' )
			->once()
			->with( $sql, $table, $column, $value )
			->andReturn( $query );

		$this->wpdb
			->shouldReceive( 'get_var' )
			->once()
			->with( $query )
			->andReturn( 1 );

		self::assertTrue( $this->db->exists_by_column( $table, $column, $value ) );
	}

	#[Test]
	public function exists_by_column_uses_bool_placeholder_when_value_is_bool(): void {

		$table = 'wp_table';
		$column = 'is_active';
		$value = false;

		$sql = 'SELECT 1 FROM %i WHERE %i = %d LIMIT 1';
		$query = 'prepared_query';

		$this->wpdb
			->shouldReceive( 'prepare' )
			->once()
			->with( $sql, $table, $column, 0 )
			->andReturn( $query );

		$this->wpdb
			->shouldReceive( 'get_var' )
			->once()
			->with( $query )
			->andReturn( 1 );

		self::assertTrue( $this->db->exists_by_column( $table, $column, $value ) );
	}

	#[Test]
	public function exists_by_column_uses_is_null_comparison_when_value_is_null(): void {

		$table = 'wp_table';
		$column = 'deleted_at';
		$value = null;

		$sql = 'SELECT 1 FROM %i WHERE %i IS NULL LIMIT 1';
		$query = 'prepared_query';

		$this->wpdb
			->shouldReceive( 'prepare' )
			->once()
			->with( $sql, $table, $column )
			->andReturn( $query );

		$this->wpdb
			->shouldReceive( 'get_var' )
			->once()
			->with( $query )
			->andReturn( 1 );

		self::assertTrue( $this->db->exists_by_column( $table, $column, $value ) );
	}

	#[Test]
	public function exists_by_column_throws_when_query_fails(): void {

		$table = 'wp_table';
		$column = 'email';
		$value = 'a@b.com';

		$sql = 'SELECT 1 FROM %i WHERE %i = %s LIMIT 1';
		$query = 'prepared_query';

		$this->wpdb
			->shouldReceive( 'prepare' )
			->once()
			->with( $sql, $table, $column, $value )
			->andReturn( $query );

		$this->wpdb
			->shouldReceive( 'get_var' )
			->once()
			->with( $query )
			->andReturn( null );

		$this->wpdb->last_error = 'Boom';

		$this->expectException( WpdbDatabaseException::class );
		$this->expectExceptionMessage(
			'Failed to check row existence for table "wp_table", column "email", value "a@b.com".',
		);

		$this->db->exists_by_column( $table, $column, $value );
	}

	// ---------------------------------------------------------------------
	// insert()
	// ---------------------------------------------------------------------

	#[Test]
	public function insert_calls_wpdb_and_succeeds_when_result_is_truthy(): void {

		$table = 'wp_table';
		$data = [ 'title' => 'A' ];

		$this->wpdb
			->shouldReceive( 'insert' )
			->once()
			->with( $table, $data )
			->andReturn( 1 );

		$this->db->insert( $table, $data );

		self::assertSame( '', $this->wpdb->last_error );
	}

	#[Test]
	public function insert_throws_when_wpdb_returns_false(): void {

		$table = 'wp_table';
		$data = [ 'title' => 'A' ];

		$this->wpdb
			->shouldReceive( 'insert' )
			->once()
			->with( $table, $data )
			->andReturn( false );

		$this->expectException( WpdbDatabaseException::class );
		$this->expectExceptionMessage( 'Failed to insert row into table "wp_table".' );

		$this->db->insert( $table, $data );
	}

	public function insert_throws_when_wpdb_last_error_is_set(): void {

		$table = 'wp_table';
		$data = [ 'title' => 'A' ];

		$this->wpdb
			->shouldReceive( 'insert' )
			->once()
			->with( $table, $data )
			->andReturn( 1 );

		$this->wpdb->last_error = 'Insert failed';

		$this->expectException( WpdbDatabaseException::class );
		$this->expectExceptionMessage( 'Failed to insert row into table "wp_table".' );

		$this->db->insert( $table, $data );
	}

	// ---------------------------------------------------------------------
	// update()
	// ---------------------------------------------------------------------

	#[Test]
	public function update_calls_wpdb_and_returns_affected_rows(): void {

		$table = 'wp_table';
		$data = [ 'title' => 'B' ];
		$where = [ 'status' => 'open' ];

		$this->wpdb
			->shouldReceive( 'update' )
			->once()
			->with( $table, $data, $where )
			->andReturn( 2 );

		self::assertSame( 2, $this->db->update( $table, $data, $where ) );
	}

	#[Test]
	public function update_throws_when_wpdb_returns_false(): void {

		$table = 'wp_table';
		$data = [ 'title' => 'B' ];
		$where = [ 'status' => 'open' ];

		$this->wpdb
			->shouldReceive( 'update' )
			->once()
			->with( $table, $data, $where )
			->andReturn( false );

		$this->expectException( WpdbDatabaseException::class );
		$this->expectExceptionMessage( 'Failed to update rows in table "wp_table".' );

		$this->db->update( $table, $data, $where );
	}

	#[Test]
	public function update_throws_when_wpdb_last_error_is_set(): void {

		$table = 'wp_table';
		$data = [ 'title' => 'A' ];
		$where = [ 'status' => 'open' ];

		$this->wpdb
			->shouldReceive( 'update' )
			->once()
			->with( $table, $data, $where )
			->andReturn( 1 );

		$this->wpdb->last_error = 'Update failed';

		$this->expectException( WpdbDatabaseException::class );
		$this->expectExceptionMessage( 'Failed to update rows in table "wp_table".' );

		$this->db->update( $table, $data, $where );
	}

	// ---------------------------------------------------------------------
	// apply_numeric_deltas()
	// ---------------------------------------------------------------------

	#[Test]
	public function apply_numeric_deltas_prepares_and_executes_atomic_update_query(): void {

		$table = 'fundrik_campaigns';
		$id = 7;
		$deltas = [
			'collected_amount' => 500,
			'donations_count' => 1,
		];

		$sql = 'UPDATE %i SET %i = %i + %d, %i = %i + %d WHERE id = %d';
		$query = 'prepared_query';

		$this->wpdb
			->shouldReceive( 'prepare' )
			->once()
			->with(
				$sql,
				'wp_fundrik_campaigns',
				'collected_amount',
				'collected_amount',
				500,
				'donations_count',
				'donations_count',
				1,
				7,
			)
			->andReturn( $query );

		$this->wpdb
			->shouldReceive( 'query' )
			->once()
			->with( $query )
			->andReturn( 1 );

		$this->db->apply_numeric_deltas( $table, $id, $deltas );
		$this->addToAssertionCount( 1 );
	}

	#[Test]
	public function apply_numeric_deltas_uses_string_placeholder_when_id_is_string(): void {

		$table = 'fundrik_campaigns';
		$id = 'abc';
		$deltas = [
			'collected_amount' => 500,
		];

		$sql = 'UPDATE %i SET %i = %i + %d WHERE id = %s';
		$query = 'prepared_query';

		$this->wpdb
			->shouldReceive( 'prepare' )
			->once()
			->with(
				$sql,
				'wp_fundrik_campaigns',
				'collected_amount',
				'collected_amount',
				500,
				'abc',
			)
			->andReturn( $query );

		$this->wpdb
			->shouldReceive( 'query' )
			->once()
			->with( $query )
			->andReturn( 1 );

		$this->db->apply_numeric_deltas( $table, $id, $deltas );
		$this->addToAssertionCount( 1 );
	}

	#[Test]
	public function apply_numeric_deltas_throws_when_delta_list_is_empty(): void {

		$this->expectException( WpdbDatabaseException::class );
		$this->expectExceptionMessage( 'Numeric deltas must be non-empty. Given: empty array.' );

		$this->db->apply_numeric_deltas( 'fundrik_campaigns', 7, [] );
	}

	#[Test]
	public function apply_numeric_deltas_throws_when_any_delta_is_zero(): void {

		$this->expectException( WpdbDatabaseException::class );
		$this->expectExceptionMessage( 'Numeric delta for column "collected_amount" must be non-zero. Given: 0.' );

		$this->db->apply_numeric_deltas(
			'fundrik_campaigns',
			7,
			[ 'collected_amount' => 0 ],
		);
	}

	#[Test]
	public function apply_numeric_deltas_throws_when_prepare_returns_invalid_value(): void {

		$sql = 'UPDATE %i SET %i = %i + %d WHERE id = %d';
		$args = [ 'wp_fundrik_campaigns', 'collected_amount', 'collected_amount', 500, 7 ];

		$this->wpdb
			->shouldReceive( 'prepare' )
			->once()
			->with( $sql, ...$args )
			->andReturn( null );

		$this->expectException( WpdbDatabaseException::class );
		$this->expectExceptionMessage( 'Prepared query must be a string. Given: null.' );

		$this->db->apply_numeric_deltas(
			'fundrik_campaigns',
			7,
			[ 'collected_amount' => 500 ],
		);
	}

	#[Test]
	public function apply_numeric_deltas_throws_when_row_is_not_found(): void {

		$query = 'prepared_query';

		$this->wpdb
			->shouldReceive( 'prepare' )
			->once()
			->with(
				'UPDATE %i SET %i = %i + %d WHERE id = %d',
				'wp_fundrik_campaigns',
				'collected_amount',
				'collected_amount',
				500,
				7,
			)
			->andReturn( $query );

		$this->wpdb
			->shouldReceive( 'query' )
			->once()
			->with( $query )
			->andReturn( 0 );

		$this->expectException( WpdbRowNotFoundException::class );
		$this->expectExceptionMessage( 'Cannot apply numeric deltas to row "7" in table "wp_fundrik_campaigns": row not found.' );

		$this->db->apply_numeric_deltas(
			'fundrik_campaigns',
			7,
			[ 'collected_amount' => 500 ],
		);
	}

	#[Test]
	public function apply_numeric_deltas_throws_when_query_execution_fails(): void {

		$query = 'prepared_query';

		$this->wpdb
			->shouldReceive( 'prepare' )
			->once()
			->with(
				'UPDATE %i SET %i = %i + %d WHERE id = %d',
				'wp_fundrik_campaigns',
				'collected_amount',
				'collected_amount',
				500,
				7,
			)
			->andReturn( $query );

		$this->wpdb
			->shouldReceive( 'query' )
			->once()
			->with( $query )
			->andReturn( false );

		$this->expectException( WpdbDatabaseException::class );
		$this->expectExceptionMessage( 'Failed to apply numeric deltas to row "7" in table "wp_fundrik_campaigns".' );

		$this->db->apply_numeric_deltas(
			'fundrik_campaigns',
			7,
			[ 'collected_amount' => 500 ],
		);
	}

	// ---------------------------------------------------------------------
	// delete()
	// ---------------------------------------------------------------------

	#[Test]
	public function delete_calls_wpdb_and_succeeds_when_result_is_truthy(): void {

		$table = 'wp_table';
		$id = 7;

		$this->wpdb
			->shouldReceive( 'delete' )
			->once()
			->with( $table, [ 'id' => $id ] )
			->andReturn( 1 );

		$this->db->delete( $table, $id );
	}

	#[Test]
	public function delete_prefixes_table_name_when_unprefixed(): void {

		$table = 'table';
		$id = 7;

		$this->wpdb
			->shouldReceive( 'delete' )
			->once()
			->with( 'wp_table', [ 'id' => $id ] )
			->andReturn( 1 );

		$this->db->delete( $table, $id );
	}

	#[Test]
	public function delete_throws_when_wpdb_returns_false(): void {

		$table = 'wp_table';
		$id = 7;

		$this->wpdb
			->shouldReceive( 'delete' )
			->once()
			->with( $table, [ 'id' => $id ] )
			->andReturn( false );

		$this->expectException( WpdbDatabaseException::class );
		$this->expectExceptionMessage( 'Failed to delete row "7" from table "wp_table".' );

		$this->db->delete( $table, $id );
	}

	#[Test]
	public function delete_throws_when_row_is_not_found(): void {

		$table = 'wp_table';
		$id = 7;

		$this->wpdb
			->shouldReceive( 'delete' )
			->once()
			->with( $table, [ 'id' => $id ] )
			->andReturn( 0 );

		$this->expectException( WpdbRowNotFoundException::class );
		$this->expectExceptionMessage( 'Cannot delete row "7" from table "wp_table": row not found.' );

		$this->db->delete( $table, $id );
	}

	#[Test]
	public function delete_throws_when_wpdb_last_error_is_set(): void {

		$table = 'wp_table';
		$id = 7;

		$this->wpdb
			->shouldReceive( 'delete' )
			->once()
			->with( $table, [ 'id' => $id ] )
			->andReturn( 1 );

		$this->wpdb->last_error = 'Delete failed';

		$this->expectException( WpdbDatabaseException::class );
		$this->expectExceptionMessage( 'Failed to delete row "7" from table "wp_table".' );

		$this->db->delete( $table, $id );
	}

	// ---------------------------------------------------------------------
	// query()
	// ---------------------------------------------------------------------

	#[Test]
	public function query_calls_wpdb_and_succeeds_when_result_is_truthy(): void {

		$sql = 'SELECT 1';

		$this->wpdb
			->shouldReceive( 'query' )
			->once()
			->with( $sql )
			->andReturn( 1 );

		$this->db->query( $sql );
	}

	#[Test]
	public function query_throws_when_wpdb_returns_false(): void {

		$sql = 'SELECT 1';

		$this->wpdb
			->shouldReceive( 'query' )
			->once()
			->with( $sql )
			->andReturn( false );

		$this->expectException( WpdbDatabaseException::class );
		$this->expectExceptionMessage( 'Failed to execute database query.' );

		$this->db->query( $sql );
	}

	#[Test]
	public function query_throws_when_wpdb_last_error_is_set(): void {

		$sql = 'SELECT 1';

		$this->wpdb
			->shouldReceive( 'query' )
			->once()
			->with( $sql )
			->andReturn( 1 );

		$this->wpdb->last_error = 'Query failed';

		$this->expectException( WpdbDatabaseException::class );
		$this->expectExceptionMessage( 'Failed to execute database query.' );

		$this->db->query( $sql );
	}

	// ---------------------------------------------------------------------
	// query_with_args()
	// ---------------------------------------------------------------------

	#[Test]
	public function query_with_args_prepares_and_executes_query(): void {

		$sql = 'UPDATE %i SET title = %s WHERE id = %d';
		$args = [ 'wp_table', 'A', 7 ];
		$prepared_sql = 'prepared_sql';

		$this->wpdb
			->shouldReceive( 'prepare' )
			->once()
			->with( $sql, ...$args )
			->andReturn( $prepared_sql );

		$this->wpdb
			->shouldReceive( 'query' )
			->once()
			->with( $prepared_sql )
			->andReturn( 1 );

		$this->db->query_with_args( $sql, ...$args );
	}

	#[Test]
	public function query_with_args_throws_when_prepare_returns_invalid_value(): void {

		$sql = 'SELECT * FROM %i WHERE id = %d';
		$args = [ 'wp_table', 7 ];

		$this->wpdb
			->shouldReceive( 'prepare' )
			->once()
			->with( $sql, ...$args )
			->andReturn( null );

		$this->expectException( WpdbDatabaseException::class );
		$this->expectExceptionMessage( 'Prepared query must be a string. Given: null.' );

		$this->db->query_with_args( $sql, ...$args );
	}

	#[Test]
	public function query_with_args_throws_when_query_execution_fails(): void {

		$sql = 'DELETE FROM %i WHERE id = %d';
		$args = [ 'wp_table', 7 ];
		$prepared_sql = 'prepared_sql';

		$this->wpdb
			->shouldReceive( 'prepare' )
			->once()
			->with( $sql, ...$args )
			->andReturn( $prepared_sql );

		$this->wpdb
			->shouldReceive( 'query' )
			->once()
			->with( $prepared_sql )
			->andReturn( false );

		$this->expectException( WpdbDatabaseException::class );
		$this->expectExceptionMessage( 'Failed to execute database query.' );

		$this->db->query_with_args( $sql, ...$args );
	}

	// ---------------------------------------------------------------------
	// qualify_table_name()
	// ---------------------------------------------------------------------

	#[Test]
	public function qualify_table_name_prefixes_unprefixed_name(): void {

		self::assertSame( 'wp_table', $this->db->qualify_table_name( 'table' ) );
	}

	#[Test]
	public function qualify_table_name_does_not_double_prefix_when_name_is_prefixed(): void {

		self::assertSame( 'wp_table', $this->db->qualify_table_name( 'wp_table' ) );
	}

	#[Test]
	public function qualify_table_name_throws_when_wpdb_returns_invalid_prefix_type(): void {

		$this->wpdb->prefix = [ 'invalid' ];

		$this->expectException( WpdbDatabaseException::class );
		$this->expectExceptionMessage( 'Database table prefix must be a string. Given: array.' );

		$this->db->qualify_table_name( 'table' );
	}

	// ---------------------------------------------------------------------
	// get_charset_collate()
	// ---------------------------------------------------------------------

	#[Test]
	public function get_charset_collate_returns_value(): void {

		$this->wpdb
			->shouldReceive( 'get_charset_collate' )
			->once()
			->andReturn( 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci' );

		self::assertSame(
			'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
			$this->db->get_charset_collate(),
		);
	}

	#[Test]
	public function get_charset_collate_throws_when_wpdb_returns_empty_string(): void {

		$this->wpdb
			->shouldReceive( 'get_charset_collate' )
			->once()
			->andReturn( '' );

		$this->expectException( WpdbDatabaseException::class );
		$this->expectExceptionMessage( 'Database charset and collation must be non-empty. Given: empty string.' );

		$this->db->get_charset_collate();
	}
}
