<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration;

use Fundrik\WordPress\Infrastructure\DatabasePort;
use wpdb;

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
				'Cannot initialize database adapter: global $wpdb is not available or has invalid type.',
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
	public function get_by_id( string $table, int|string $id ): ?array {

		$table = $this->qualify_table_name( $table );
		$placeholder = is_int( $id ) ? '%d' : '%s';

		$sql = "SELECT * FROM %i WHERE id = {$placeholder} LIMIT 1";
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$query = $this->wpdb->prepare( $sql, $table, $id );

		// phpcs:ignore Generic.Commenting.DocComment.MissingShort, SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
		/** @var array<string, mixed>|null $row */
		$row = $this->wpdb->get_row( $query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( $this->wpdb->last_error !== '' ) {

			throw new WpdbDatabaseException(
				sprintf(
					'Cannot fetch row by ID: database query failed for table "%s", error: %s. Given: %s.',
					$table,
					$this->wpdb->last_error,
					(string) $id,
				),
			);
		}

		if ( $row === null ) {
			return null;
		}

		return $this->sanitize_db_row( $row );
	}

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
	/**
	 * Retrieves all rows from the given table.
	 *
	 * @since 1.0.0
	 *
	 * @param string $table The table name.
	 *
	 * @return array<array<string, int|float|string|bool|null>> The list of rows.
	 *
	 * @phpstan-return list<array<string, int|float|string|bool|null>>
	 *
	 * @throws WpdbDatabaseException When the query fails.
	 */
	public function get_all( string $table ): array {

		$table = $this->qualify_table_name( $table );
		$sql = 'SELECT * FROM %i';
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$query = $this->wpdb->prepare( $sql, $table );

		// phpcs:ignore Generic.Commenting.DocComment.MissingShort, SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
		/** @var list<array<string, mixed>>|null $results */
		$results = $this->wpdb->get_results( $query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( $this->wpdb->last_error !== '' ) {

			throw new WpdbDatabaseException(
				sprintf(
					'Cannot fetch rows: database query failed for table "%s". Error: %s.',
					$table,
					$this->wpdb->last_error,
				),
			);
		}

		if ( ! is_array( $results ) ) {
			return [];
		}

		$casted_results = [];

		foreach ( $results as $row ) {

			$casted_results[] = $this->sanitize_db_row( $row );
		}

		return $casted_results;
	}
	// phpcs:enable

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
	public function exists_by_id( string $table, int|string $id ): bool {

		$table = $this->qualify_table_name( $table );
		$placeholder = is_int( $id ) ? '%d' : '%s';

		$sql = "SELECT 1 FROM %i WHERE id = {$placeholder} LIMIT 1";
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$query = $this->wpdb->prepare( $sql, $table, $id );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$exists = $this->wpdb->get_var( $query );

		if ( $this->wpdb->last_error !== '' ) {

			throw new WpdbDatabaseException(
				sprintf(
					'Cannot check row existence: database query failed for table "%s". Error: %s. Given: %s.',
					$table,
					$this->wpdb->last_error,
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
	public function exists_by_column( string $table, string $column, int|float|string|bool|null $value ): bool {

		$table = $this->qualify_table_name( $table );
		$placeholder = is_int( $value ) ? '%d' : '%s';

		$sql = "SELECT 1 FROM %i WHERE %i = {$placeholder} LIMIT 1";
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$query = $this->wpdb->prepare( $sql, $table, $column, $value );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$exists = $this->wpdb->get_var( $query );

		if ( $this->wpdb->last_error !== '' ) {

			throw new WpdbDatabaseException(
				sprintf(
					// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
					'Cannot check row existence: database query failed for table "%s", column "%s". Error: %s. Given: %s.',
					$table,
					$column,
					$this->wpdb->last_error,
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
	public function insert( string $table, array $data ): void {

		$table = $this->qualify_table_name( $table );
		$result = $this->wpdb->insert( $table, $data );

		if ( $result === false || $this->wpdb->last_error !== '' ) {

			throw new WpdbDatabaseException(
				sprintf(
					'Cannot insert row: database operation failed for table "%s". Error: %s.',
					$table,
					$this->wpdb->last_error !== '' ? $this->wpdb->last_error : 'Unknown error',
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
	public function update( string $table, array $data, array $where ): int {

		$table = $this->qualify_table_name( $table );
		$result = $this->wpdb->update( $table, $data, $where );

		if ( $result === false || $this->wpdb->last_error !== '' ) {

			throw new WpdbDatabaseException(
				sprintf(
					'Cannot update row(s): database operation failed for table "%s". Error: %s.',
					$table,
					$this->wpdb->last_error !== '' ? $this->wpdb->last_error : 'Unknown error',
				),
			);
		}

		return $result;
	}

	/**
	 * Deletes the row with the given ID.
	 *
	 * @since 1.0.0
	 *
	 * @param string $table The table name.
	 * @param int|string $id The row ID to delete.
	 *
	 * @throws WpdbDatabaseException When the delete fails.
	 */
	public function delete( string $table, int|string $id ): void {

		$table = $this->qualify_table_name( $table );
		$result = $this->wpdb->delete(
			$table,
			[ 'id' => $id ],
		);

		if ( $result === false || $this->wpdb->last_error !== '' ) {

			throw new WpdbDatabaseException(
				sprintf(
					'Cannot delete row: database operation failed for table "%s". Error: %s. Given: %s.',
					$table,
					$this->wpdb->last_error !== '' ? $this->wpdb->last_error : 'Unknown error',
					(string) $id,
				),
			);
		}
	}

	/**
	 * Executes a raw SQL query.
	 *
	 * @since 1.0.0
	 *
	 * @param string $sql The SQL query to execute.
	 *
	 * @throws WpdbDatabaseException When execution fails.
	 */
	public function query( string $sql ): void {

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$result = $this->wpdb->query( $sql );

		if ( $result === false || $this->wpdb->last_error !== '' ) {

			throw new WpdbDatabaseException(
				sprintf(
					'Cannot execute query: database operation failed. Error: %s.',
					$this->wpdb->last_error !== '' ? $this->wpdb->last_error : 'Unknown error',
				),
			);
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
	public function query_with_args( string $sql, int|float|string|bool|null ...$args ): void {

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$query = $this->wpdb->prepare( $sql, ...$args );

		if ( ! is_string( $query ) ) {

			throw new WpdbDatabaseException(
				sprintf(
					'Cannot prepare query: wpdb->prepare() must return a non-empty string. Given: %s.',
					get_debug_type( $query ),
				),
			);
		}

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
	public function get_charset_collate(): string {

		$charset_collate = $this->wpdb->get_charset_collate();

		if ( $charset_collate === '' ) {

			throw new WpdbDatabaseException(
				'Cannot determine database charset and collate: wpdb returned an empty collation string.',
			);
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
	public function qualify_table_name( string $table ): string {

		$prefix = $this->wpdb->prefix;

		if ( ! is_string( $prefix ) ) {

			throw new WpdbDatabaseException(
				sprintf(
					'Cannot determine database table prefix: wpdb returned invalid type %s.',
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
}
