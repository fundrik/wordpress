<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Integration;

use Fundrik\WordPress\Infrastructure\Database\DatabaseException;
use Fundrik\WordPress\Infrastructure\Database\DatabaseInterface;
use wpdb;

/**
 * Provides access to WordPress database queries and schema metadata.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class WpdbDatabase implements DatabaseInterface {

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

			throw new DatabaseException(
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
	 * @throws DatabaseException When the query fails.
	 */
	public function get_by_id( string $table, int|string $id ): ?array {

		$placeholder = is_int( $id ) ? '%d' : '%s';

		$sql = "SELECT * FROM %i WHERE id = {$placeholder} LIMIT 1";
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$query = $this->wpdb->prepare( $sql, $table, $id );

		// phpcs:ignore Generic.Commenting.DocComment.MissingShort, SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
		/** @var array<string, mixed>|null $row */
		$row = $this->wpdb->get_row( $query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( $this->wpdb->last_error !== '' ) {

			throw new DatabaseException(
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
	 * @throws DatabaseException When the query fails.
	 */
	public function get_all( string $table ): array {

		$sql = 'SELECT * FROM %i';
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$query = $this->wpdb->prepare( $sql, $table );

		// phpcs:ignore Generic.Commenting.DocComment.MissingShort, SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
		/** @var list<array<string, mixed>>|null $results */
		$results = $this->wpdb->get_results( $query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( $this->wpdb->last_error !== '' ) {

			throw new DatabaseException(
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

		foreach ( $results as $index => $row ) {

			$casted_results[ $index ] = $this->sanitize_db_row( $row );
		}

		return $casted_results;
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
	 * @throws DatabaseException When the query fails.
	 */
	public function exists_by_id( string $table, int|string $id ): bool {

		$placeholder = is_int( $id ) ? '%d' : '%s';

		$sql = "SELECT 1 FROM %i WHERE id = {$placeholder} LIMIT 1";
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$query = $this->wpdb->prepare( $sql, $table, $id );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$exists = $this->wpdb->get_var( $query );

		if ( $this->wpdb->last_error !== '' ) {

			throw new DatabaseException(
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
	 * @throws DatabaseException When the query fails.
	 */
	public function exists_by_column( string $table, string $column, int|float|string|bool|null $value ): bool {

		$placeholder = is_int( $value ) ? '%d' : '%s';

		$sql = "SELECT 1 FROM %i WHERE %i = {$placeholder} LIMIT 1";
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$query = $this->wpdb->prepare( $sql, $table, $column, $value );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$exists = $this->wpdb->get_var( $query );

		if ( $this->wpdb->last_error !== '' ) {

			throw new DatabaseException(
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
	 * @throws DatabaseException When the insert fails.
	 */
	public function insert( string $table, array $data ): void {

		$result = $this->wpdb->insert( $table, $data );

		if ( $result === false || $this->wpdb->last_error !== '' ) {

			throw new DatabaseException(
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
	 * @throws DatabaseException When the update fails.
	 */
	public function update( string $table, array $data, array $where ): int {

		$result = $this->wpdb->update( $table, $data, $where );

		if ( $result === false || $this->wpdb->last_error !== '' ) {

			throw new DatabaseException(
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
	 * @throws DatabaseException When the delete fails.
	 */
	public function delete( string $table, int|string $id ): void {

		$result = $this->wpdb->delete(
			$table,
			[ 'id' => $id ],
		);

		if ( $result === false || $this->wpdb->last_error !== '' ) {

			throw new DatabaseException(
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
	 * @throws DatabaseException When execution fails.
	 */
	public function query( string $sql ): void {

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$result = $this->wpdb->query( $sql );

		if ( $result === false || $this->wpdb->last_error !== '' ) {

			throw new DatabaseException(
				sprintf(
					'Cannot execute query: database operation failed. Error: %s.',
					$this->wpdb->last_error !== '' ? $this->wpdb->last_error : 'Unknown error',
				),
			);
		}
	}

	/**
	 * Returns the charset and collation string for the database.
	 *
	 * @since 1.0.0
	 *
	 * @return string The charset and collation string, e.g. "DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci".
	 *
	 * @throws DatabaseException When the information cannot be determined.
	 */
	public function get_charset_collate(): string {

		$charset_collate = $this->wpdb->get_charset_collate();

		if ( $charset_collate === '' ) {

			throw new DatabaseException(
				'Cannot determine database charset and collate: wpdb returned an empty collation string.',
			);
		}

		return $charset_collate;
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
	 * @throws DatabaseException When a non-scalar value is encountered.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function sanitize_db_row( array $raw_row ): array {

		$sanitized = [];

		foreach ( $raw_row as $key => $value ) {

			if ( $value !== null && ! is_scalar( $value ) ) {

				throw new DatabaseException(
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
