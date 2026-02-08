<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\Integration;

use Fundrik\WordPress\Infrastructure\Database\DatabaseException;
use Fundrik\WordPress\Infrastructure\Database\DatabaseInterface;
use Fundrik\WordPress\Integration\WpdbDatabase;
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

		// phpcs:ignore SlevomatCodingStandard.Variables.DisallowSuperGlobalVariable.DisallowedSuperGlobalVariable, WordPress.WP.GlobalVariablesOverride.Prohibited
		$GLOBALS['wpdb'] = $this->wpdb;

		$this->db = new WpdbDatabase();
	}

	#[Test]
	public function it_implements_database_interface(): void {

		self::assertInstanceOf( DatabaseInterface::class, $this->db );
	}

	#[Test]
	public function constructor_throws_when_global_wpdb_is_missing(): void {

		// phpcs:ignore SlevomatCodingStandard.Variables.DisallowSuperGlobalVariable.DisallowedSuperGlobalVariable
		unset( $GLOBALS['wpdb'] );

		$this->expectException( DatabaseException::class );
		$this->expectExceptionMessage(
			'Cannot initialize database adapter: global $wpdb is not available or has invalid type.',
		);

		new WpdbDatabase();
	}

	#[Test]
	public function constructor_throws_when_global_wpdb_has_invalid_type(): void {

		// phpcs:ignore SlevomatCodingStandard.Variables.DisallowSuperGlobalVariable.DisallowedSuperGlobalVariable, WordPress.WP.GlobalVariablesOverride.Prohibited
		$GLOBALS['wpdb'] = new stdClass();

		$this->expectException( DatabaseException::class );
		$this->expectExceptionMessage(
			'Cannot initialize database adapter: global $wpdb is not available or has invalid type.',
		);

		new WpdbDatabase();
	}

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
	public function get_by_id_prepares_query_with_string_placeholder(): void {

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

		$this->expectException( DatabaseException::class );
		$this->expectExceptionMessage(
			'Cannot fetch row by ID: database query failed for table "wp_table", error: Boom. Given: 7.',
		);

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

		$this->expectException( DatabaseException::class );
		$this->expectExceptionMessage( 'DB value must be scalar or null. Key: "bad". Given: array.' );

		$this->db->get_by_id( $table, $id );
	}

	#[Test]
	public function get_all_returns_rows_as_list(): void {

		$table = 'wp_table';

		$sql = 'SELECT * FROM %i';
		$query = 'prepared_query';

		$results = [
			[ 'id' => 2 ],
			[ 'id' => 1 ],
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

		$this->expectException( DatabaseException::class );
		$this->expectExceptionMessage( 'Cannot fetch rows: database query failed for table "wp_table". Error: Boom.' );

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

		$this->expectException( DatabaseException::class );
		$this->expectExceptionMessage( 'DB value must be scalar or null. Key: "bad". Given: array.' );

		$this->db->get_all( $table );
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

		$this->expectException( DatabaseException::class );
		$this->expectExceptionMessage(
			'Cannot check row existence: database query failed for table "wp_table". Error: Boom. Given: 7.',
		);

		$this->db->exists_by_id( $table, $id );
	}

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

		$this->expectException( DatabaseException::class );
		$this->expectExceptionMessage(
			'Cannot check row existence: database query failed for table "wp_table", column "email". Error: Boom. Given: a@b.com.',
		);

		$this->db->exists_by_column( $table, $column, $value );
	}

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

		$this->expectException( DatabaseException::class );
		$this->expectExceptionMessage(
			'Cannot insert row: database operation failed for table "wp_table". Error: Unknown error.',
		);

		$this->db->insert( $table, $data );
	}

	#[Test]
	public function insert_throws_when_wpdb_last_error_is_set(): void {

		$table = 'wp_table';
		$data = [ 'title' => 'A' ];

		$this->wpdb
			->shouldReceive( 'insert' )
			->once()
			->with( $table, $data )
			->andReturn( 1 );

		$this->wpdb->last_error = 'Insert failed';

		$this->expectException( DatabaseException::class );
		$this->expectExceptionMessage(
			'Cannot insert row: database operation failed for table "wp_table". Error: Insert failed.',
		);

		$this->db->insert( $table, $data );
	}

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

		$this->expectException( DatabaseException::class );
		$this->expectExceptionMessage(
			'Cannot update row(s): database operation failed for table "wp_table". Error: Unknown error.',
		);

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

		$this->expectException( DatabaseException::class );
		$this->expectExceptionMessage(
			'Cannot update row(s): database operation failed for table "wp_table". Error: Update failed.',
		);

		$this->db->update( $table, $data, $where );
	}

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
	public function delete_throws_when_wpdb_returns_false(): void {

		$table = 'wp_table';
		$id = 7;

		$this->wpdb
			->shouldReceive( 'delete' )
			->once()
			->with( $table, [ 'id' => $id ] )
			->andReturn( false );

		$this->expectException( DatabaseException::class );
		$this->expectExceptionMessage(
			'Cannot delete row: database operation failed for table "wp_table". Error: Unknown error. Given: 7.',
		);

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

		$this->expectException( DatabaseException::class );
		$this->expectExceptionMessage(
			'Cannot delete row: database operation failed for table "wp_table". Error: Delete failed. Given: 7.',
		);

		$this->db->delete( $table, $id );
	}

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

		$this->expectException( DatabaseException::class );
		$this->expectExceptionMessage( 'Cannot execute query: database operation failed. Error: Unknown error.' );

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

		$this->expectException( DatabaseException::class );
		$this->expectExceptionMessage( 'Cannot execute query: database operation failed. Error: Query failed.' );

		$this->db->query( $sql );
	}

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

		$this->expectException( DatabaseException::class );
		$this->expectExceptionMessage(
			'Cannot determine database charset and collate: wpdb returned an empty collation string.',
		);

		$this->db->get_charset_collate();
	}
}
