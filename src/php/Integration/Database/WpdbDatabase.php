<?php // phpcs:ignore SlevomatCodingStandard.Files.FileLength.FileTooLong


declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Database;

use Fundrik\WordPress\Infrastructure\Ports\Database\DatabasePort;
use Override;
use wpdb;

// phpcs:disable SlevomatCodingStandard.Classes.ClassLength.ClassTooLong
/**
 * Provides access to WordPress database queries and schema metadata.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class WpdbDatabase implements DatabasePort {

	/**
	 * Provides access to the global WordPress database connection.
	 *
	 * @since 1.0.0
	 */
	private wpdb $wpdb;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// phpcs:ignore SlevomatCodingStandard.Variables.DisallowSuperGlobalVariable.DisallowedSuperGlobalVariable
		$wpdb = $GLOBALS['wpdb'] ?? null;

		if ( ! $wpdb instanceof wpdb ) {

			throw new WpdbDatabaseException(
				sprintf(
					'Global $wpdb must be an instance of wpdb. Given: %s.',
					get_debug_type( $wpdb ),
				),
			);
		}

		$this->wpdb = $wpdb;
	}

	/**
	 * Fetches the row with the given ID.
	 *
	 * @since 1.0.0
	 *
	 * @param string $table The table name.
	 * @param int|string $id The row ID to fetch.
	 *
	 * @return array<string, int|float|string|bool|null>|null The result row, or null if not found.
	 *
	 * @throws WpdbDatabaseException When the query fails.
	 */
	#[Override]
	public function get_by_id( string $table, int|string $id ): ?array {

		$table = $this->qualify_table_name( $table );
		$placeholder = is_int( $id ) ? '%d' : '%s';

		$sql = "SELECT * FROM %i WHERE id = {$placeholder} LIMIT 1";
		$query = $this->prepare_query( $sql, $table, $id );

		// phpcs:ignore Generic.Commenting.DocComment.MissingShort, SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
		/** @var array<string, mixed>|null $row */
		$row = $this->wpdb->get_row( $query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( $this->wpdb->last_error !== '' ) {

			throw new WpdbDatabaseException(
				sprintf(
					'Failed to fetch row "%s" from table "%s".',
					(string) $id,
					$table,
				),
			);
		}

		if ( $row === null ) {
			return null;
		}

		return $this->sanitize_db_row( $row );
	}

	/**
	 * Retrieves all rows from the given table.
	 *
	 * @since 1.0.0
	 *
	 * @param string $table The table name.
	 *
	 * @return list<array<string, int|float|string|bool|null>> The list of rows.
	 *
	 * @throws WpdbDatabaseException When the query fails.
	 */
	#[Override]
	public function get_all( string $table ): array {

		$table = $this->qualify_table_name( $table );
		$sql = 'SELECT * FROM %i';
		$query = $this->prepare_query( $sql, $table );

		// phpcs:ignore Generic.Commenting.DocComment.MissingShort, SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
		/** @var list<array<string, mixed>>|null $results */
		$results = $this->wpdb->get_results( $query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( $this->wpdb->last_error !== '' ) {

			throw new WpdbDatabaseException(
				sprintf(
					'Failed to fetch rows from table "%s".',
					$table,
				),
			);
		}

		if ( ! is_array( $results ) ) {
			return [];
		}

		return $this->sanitize_db_results( $results );
	}

	/**
	 * Retrieves all rows from the given table filtered by a column value.
	 *
	 * @since 1.0.0
	 *
	 * @param string $table The table name.
	 * @param string $column The column to filter by.
	 * @param int|float|string|bool|null $value The value to match.
	 *
	 * @return list<array<string, int|float|string|bool|null>> The list of matching rows.
	 *
	 * @throws WpdbDatabaseException When the query fails.
	 */
	#[Override]
	public function get_all_by_column( string $table, string $column, int|float|string|bool|null $value ): array {

		$table = $this->qualify_table_name( $table );
		[ $where_sql, $where_args ] = $this->build_column_value_filter( $column, $value );

		$sql = "SELECT * FROM %i {$where_sql}";
		$query = $this->prepare_query( $sql, $table, ...$where_args );

		// phpcs:ignore Generic.Commenting.DocComment.MissingShort, SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
		/** @var list<array<string, mixed>>|null $results */
		$results = $this->wpdb->get_results( $query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( $this->wpdb->last_error !== '' ) {

			throw new WpdbDatabaseException(
				sprintf(
					'Failed to fetch rows from table "%s" by column "%s" and value "%s".',
					$table,
					$column,
					$value === null ? 'NULL' : (string) $value,
				),
			);
		}

		if ( ! is_array( $results ) ) {
			return [];
		}

		return $this->sanitize_db_results( $results );
	}

	/**
	 * Determines whether the table exists.
	 *
	 * @since 1.0.0
	 *
	 * @param string $table Table name.
	 *
	 * @return bool True if the table exists.
	 *
	 * @throws WpdbDatabaseException When the query fails.
	 */
	#[Override]
	public function table_exists( string $table ): bool {

		$table = $this->qualify_table_name( $table );
		$sql = 'SHOW TABLES LIKE %s';
		$query = $this->prepare_query( $sql, $table );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$exists = $this->wpdb->get_var( $query );

		if ( $this->wpdb->last_error !== '' ) {

			throw new WpdbDatabaseException(
				sprintf(
					'Failed to check table existence for table "%s".',
					$table,
				),
			);
		}

		return $exists !== null;
	}

	/**
	 * Determines whether the table contains a row with the given ID.
	 *
	 * @since 1.0.0
	 *
	 * @param string $table The table name.
	 * @param int|string $id The row ID to look up.
	 *
	 * @return bool True if a matching row exists.
	 *
	 * @throws WpdbDatabaseException When the query fails.
	 */
	#[Override]
	public function exists_by_id( string $table, int|string $id ): bool {

		$table = $this->qualify_table_name( $table );
		$placeholder = is_int( $id ) ? '%d' : '%s';

		$sql = "SELECT 1 FROM %i WHERE id = {$placeholder} LIMIT 1";
		$query = $this->prepare_query( $sql, $table, $id );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$exists = $this->wpdb->get_var( $query );

		if ( $this->wpdb->last_error !== '' ) {

			throw new WpdbDatabaseException(
				sprintf(
					'Failed to check row existence for table "%s" and ID "%s".',
					$table,
					(string) $id,
				),
			);
		}

		return $exists !== null;
	}

	/**
	 * Determines whether the table contains a row with the given column value.
	 *
	 * @since 1.0.0
	 *
	 * @param string $table The table name.
	 * @param string $column The column to filter by.
	 * @param int|float|string|bool|null $value The value to match.
	 *
	 * @return bool True if a matching row exists.
	 *
	 * @throws WpdbDatabaseException When the query fails.
	 */
	#[Override]
	public function exists_by_column( string $table, string $column, int|float|string|bool|null $value ): bool {

		$table = $this->qualify_table_name( $table );
		[ $where_sql, $where_args ] = $this->build_column_value_filter( $column, $value );

		$sql = "SELECT 1 FROM %i {$where_sql} LIMIT 1";
		$query = $this->prepare_query( $sql, $table, ...$where_args );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$exists = $this->wpdb->get_var( $query );

		if ( $this->wpdb->last_error !== '' ) {

			throw new WpdbDatabaseException(
				sprintf(
					'Failed to check row existence for table "%s", column "%s", value "%s".',
					$table,
					$column,
					$value === null ? 'NULL' : (string) $value,
				),
			);
		}

		return $exists !== null;
	}

	/**
	 * Inserts a new row into the given table.
	 *
	 * @since 1.0.0
	 *
	 * @param string $table The table name.
	 * @param array<string, int|float|string|bool|null> $data The column-value pairs to insert.
	 *
	 * @throws WpdbDatabaseException When the insert fails.
	 */
	#[Override]
	public function insert( string $table, array $data ): void {

		$table = $this->qualify_table_name( $table );
		$result = $this->wpdb->insert( $table, $data );

		if ( $result === false || $this->wpdb->last_error !== '' ) {

			if ( $this->is_duplicate_key_error() ) {
				throw new WpdbDuplicateKeyException(
					sprintf(
						'Cannot insert row into table "%s": duplicate key.',
						$table,
					),
				);
			}

			throw new WpdbDatabaseException(
				sprintf(
					'Failed to insert row into table "%s".',
					$table,
				),
			);
		}
	}

	/**
	 * Updates rows matching the given equality conditions.
	 *
	 * @since 1.0.0
	 *
	 * @param string $table The table name.
	 * @param array<string, int|float|string|bool|null> $data The column-value pairs to update.
	 * @param array<string, int|float|string|bool|null> $where The column-value pairs to match.
	 *
	 * @return int The number of affected rows.
	 *
	 * @throws WpdbDatabaseException When the update fails.
	 */
	#[Override]
	public function update( string $table, array $data, array $where ): int {

		$table = $this->qualify_table_name( $table );
		$result = $this->wpdb->update( $table, $data, $where );

		if ( $result === false || $this->wpdb->last_error !== '' ) {

			throw new WpdbDatabaseException(
				sprintf(
					'Failed to update rows in table "%s".',
					$table,
				),
			);
		}

		return $result;
	}

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
	/**
	 * Applies non-zero numeric deltas to the row with the given ID.
	 *
	 * @since 1.0.0
	 *
	 * @param string $table The table name.
	 * @param int|string $id The row ID to update.
	 * @param array<string, int> $deltas Non-empty [column => non-zero delta] map.
	 *
	 * @throws WpdbRowNotFoundException When the row does not exist.
	 * @throws WpdbDatabaseException When the update fails.
	 */
	#[Override]
	public function apply_numeric_deltas( string $table, int|string $id, array $deltas ): void {

		$table = $this->qualify_table_name( $table );
		$id_placeholder = is_int( $id ) ? '%d' : '%s';
		[ $assignments, $delta_args ] = $this->build_numeric_delta_assignments( $deltas );

		$sql = sprintf(
			'UPDATE %%i SET %s WHERE id = %s',
			implode( ', ', $assignments ),
			$id_placeholder,
		);
		$args = [ $table, ...$delta_args, $id ];

		$query = $this->prepare_query( $sql, ...$args );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$result = $this->wpdb->query( $query );

		if ( $result === false || $this->wpdb->last_error !== '' ) {
			throw new WpdbDatabaseException(
				sprintf(
					'Failed to apply numeric deltas to row "%s" in table "%s".',
					(string) $id,
					$table,
				),
			);
		}

		if ( $result === 0 ) {
			throw new WpdbRowNotFoundException(
				sprintf(
					'Cannot apply numeric deltas to row "%s" in table "%s": row not found.',
					(string) $id,
					$table,
				),
			);
		}
	}
	// phpcs:enable

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
	/**
	 * Deletes the row with the given ID.
	 *
	 * @since 1.0.0
	 *
	 * @param string $table The table name.
	 * @param int|string $id The row ID to delete.
	 *
	 * @throws WpdbRowNotFoundException When the row does not exist.
	 * @throws WpdbDatabaseException When the delete fails.
	 */
	#[Override]
	public function delete( string $table, int|string $id ): void {

		$table = $this->qualify_table_name( $table );
		$result = $this->wpdb->delete(
			$table,
			[ 'id' => $id ],
		);

		if ( $result === false || $this->wpdb->last_error !== '' ) {

			throw new WpdbDatabaseException(
				sprintf(
					'Failed to delete row "%s" from table "%s".',
					(string) $id,
					$table,
				),
			);
		}

		if ( $result === 0 ) {

			throw new WpdbRowNotFoundException(
				sprintf(
					'Cannot delete row "%s" from table "%s": row not found.',
					(string) $id,
					$table,
				),
			);
		}
	}
	// phpcs:enable

	/**
	 * Executes a raw SQL query.
	 *
	 * @since 1.0.0
	 *
	 * @param string $sql The SQL query to execute.
	 *
	 * @throws WpdbDatabaseException When execution fails.
	 */
	#[Override]
	public function query( string $sql ): void {

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$result = $this->wpdb->query( $sql );

		if ( $result === false || $this->wpdb->last_error !== '' ) {

			throw new WpdbDatabaseException( 'Failed to execute database query.' );
		}
	}

	/**
	 * Executes a SQL query with bound placeholder values.
	 *
	 * @since 1.0.0
	 *
	 * @param string $sql The SQL query template with placeholders.
	 * @param int|float|string|bool|null ...$args The values to bind to placeholders.
	 *
	 * @throws WpdbDatabaseException When preparing or executing fails.
	 */
	#[Override]
	public function query_with_args( string $sql, int|float|string|bool|null ...$args ): void {

		$query = $this->prepare_query( $sql, ...$args );

		$this->query( $query );
	}

	/**
	 * Returns the charset and collation string for the database.
	 *
	 * @since 1.0.0
	 *
	 * @return string The charset and collation string, e.g. "DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci".
	 *
	 * @throws WpdbDatabaseException When the information cannot be determined.
	 */
	#[Override]
	public function get_charset_collate(): string {

		$charset_collate = $this->wpdb->get_charset_collate();

		if ( $charset_collate === '' ) {

			throw new WpdbDatabaseException( 'Database charset and collation must be non-empty. Given: empty string.' );
		}

		return $charset_collate;
	}

	/**
	 * Resolves table name using the configured table prefix.
	 *
	 * @since 1.0.0
	 *
	 * @param string $table The table name with or without prefix.
	 *
	 * @return string The table name with prefix applied.
	 *
	 * @throws WpdbDatabaseException When the prefix cannot be determined.
	 */
	#[Override]
	public function qualify_table_name( string $table ): string {

		$prefix = $this->wpdb->prefix;

		if ( ! is_string( $prefix ) ) {

			throw new WpdbDatabaseException(
				sprintf(
					'Database table prefix must be a string. Given: %s.',
					get_debug_type( $prefix ),
				),
			);
		}

		if ( $prefix === '' || str_starts_with( $table, $prefix ) ) {
			return $table;
		}

		return $prefix . $table;
	}

	/**
	 * Validates and normalizes a list of database rows.
	 *
	 * @since 1.0.0
	 *
	 * @param list<array<string, mixed>> $results Raw rows returned by wpdb.
	 *
	 * @return list<array<string, int|float|string|bool|null>> The validated database rows.
	 *
	 * @throws WpdbDatabaseException When any row contains a non-scalar value.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function sanitize_db_results( array $results ): array {

		$sanitized_results = [];

		foreach ( $results as $row ) {

			$sanitized_results[] = $this->sanitize_db_row( $row );
		}

		return $sanitized_results;
	}

	/**
	 * Ensures that all values returned from the database are either scalar or null.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $raw_row The raw row returned by wpdb.
	 *
	 * @return array<string, int|float|string|bool|null> The validated database row.
	 *
	 * @throws WpdbDatabaseException When a non-scalar value is encountered.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function sanitize_db_row( array $raw_row ): array {

		$sanitized = [];

		foreach ( $raw_row as $key => $value ) {

			if ( $value !== null && ! is_scalar( $value ) ) {

				throw new WpdbDatabaseException(
					sprintf(
						'DB value must be scalar or null. Key: "%s". Given: %s.',
						$key,
						get_debug_type( $value ),
					),
				);
			}

			$sanitized[ $key ] = $value;
		}

		return $sanitized;
	}

	/**
	 * Builds a column comparison clause and its placeholder arguments.
	 *
	 * @since 1.0.0
	 *
	 * @param string $column The column to filter by.
	 * @param int|float|string|bool|null $value The value to match.
	 *
	 * @return array{0:string,1:list<int|float|string>} The SQL fragment and bound arguments.
	 */
	private function build_column_value_filter( string $column, int|float|string|bool|null $value ): array {

		if ( $value === null ) {
			return [ 'WHERE %i IS NULL', [ $column ] ];
		}

		$placeholder = match ( true ) {
			is_bool( $value ), is_int( $value ) => '%d',
			is_float( $value ) => '%f',
			default => '%s',
		};

		if ( is_bool( $value ) ) {
			$value = (int) $value;
		}

		return [ "WHERE %i = {$placeholder}", [ $column, $value ] ];
	}

	/**
	 * Builds assignment fragments and placeholder arguments for numeric deltas.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, int> $deltas Non-empty [column => non-zero delta] map.
	 *
	 * @return array{0:list<string>,1:list<int|string>} Assignment SQL fragments and placeholder arguments.
	 *
	 * @throws WpdbDatabaseException When the delta list is empty or contains zero values.
	 */
	private function build_numeric_delta_assignments( array $deltas ): array {

		if ( $deltas === [] ) {
			throw new WpdbDatabaseException( 'Numeric deltas must be non-empty. Given: empty array.' );
		}

		$assignments = [];
		$args = [];

		foreach ( $deltas as $column => $delta ) {

			if ( $delta === 0 ) {
				throw new WpdbDatabaseException(
					sprintf(
						'Numeric delta for column "%s" must be non-zero. Given: 0.',
						$column,
					),
				);
			}

			$assignments[] = '%i = %i + %d';
			$args[] = $column;
			$args[] = $column;
			$args[] = $delta;
		}

		return [ $assignments, $args ];
	}

	/**
	 * Prepares a SQL query and returns the sanitized query string.
	 *
	 * @since 1.0.0
	 *
	 * @param string $sql Query template with placeholders.
	 * @param int|float|string|bool|null ...$args Placeholder arguments.
	 *
	 * @return string Prepared query string.
	 *
	 * @throws WpdbDatabaseException When prepare does not return a string.
	 */
	private function prepare_query( string $sql, int|float|string|bool|null ...$args ): string {

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$query = $this->wpdb->prepare( $sql, ...$args );

		if ( ! is_string( $query ) ) {
			throw new WpdbDatabaseException(
				sprintf(
					'Prepared query must be a string. Given: %s.',
					get_debug_type( $query ),
				),
			);
		}

		return $query;
	}

	/**
	 * Returns whether the last wpdb error indicates a duplicate-key violation.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True when the last error matches duplicate-key semantics.
	 */
	private function is_duplicate_key_error(): bool {

		return preg_match(
			'/duplicate entry|duplicate key|unique constraint|unique key/i',
			$this->wpdb->last_error,
		) === 1;
	}
}
// phpcs:enable
