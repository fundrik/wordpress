<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Integration;

use Fundrik\Core\Support\TypeCaster;
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
		$wpdb = $GLOBALS['wpdb'];
		assert( $wpdb instanceof wpdb );
		$this->wpdb = $wpdb;
	}

	/**
	 * Fetches the row with the given ID.
	 *
	 * @since 1.0.0
	 *
	 * @param string $table The table name.
	 * @param int|string $id The ID of the row to fetch.
	 *
	 * @return array<string, scalar|null>|null The result row, or null if not found.
	 */
	public function get_by_id( string $table, int|string $id ): ?array {

		$placeholder = is_int( $id ) ? '%d' : '%s';

		$sql = "SELECT * FROM %i WHERE id = {$placeholder} LIMIT 1";
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$query = $this->wpdb->prepare( $sql, $table, $id );

		// phpcs:ignore Generic.Commenting.DocComment.MissingShort, SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
		/** @var array<string, mixed>|null $row */
		$row = $this->wpdb->get_row( $query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

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
	 * @return array<array<string,scalar|null>> The list of rows.
	 */
	public function get_all( string $table ): array {

		$sql = 'SELECT * FROM %i';
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$query = $this->wpdb->prepare( $sql, $table );

		// phpcs:ignore Generic.Commenting.DocComment.MissingShort, SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
		/** @var list<array<string, mixed>>|null $results */
		$results = $this->wpdb->get_results( $query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( ! is_array( $results ) ) {
			return [];
		}

		$casted_results = [];

		foreach ( $results as $index => $row ) {

			$casted_results[ $index ] = $this->sanitize_db_row( $row );
		}

		return array_values( $casted_results );
	}

	/**
	 * Determines whether the table contains a row with the given ID.
	 *
	 * @since 1.0.0
	 *
	 * @param string $table The table name.
	 * @param int|string $id The ID to look up.
	 *
	 * @return bool True if a matching row exists, false otherwise.
	 */
	public function exists( string $table, int|string $id ): bool {

		$placeholder = is_int( $id ) ? '%d' : '%s';

		$sql = "SELECT 1 FROM %i WHERE id = {$placeholder} LIMIT 1";
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$query = $this->wpdb->prepare( $sql, $table, $id );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $this->wpdb->get_var( $query ) !== null;
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
	 * @return bool True if a matching row exists, false otherwise.
	 */
	public function exists_by_column( string $table, string $column, int|float|string|bool|null $value ): bool {

		$placeholder = is_int( $value ) ? '%d' : '%s';

		$sql = "SELECT 1 FROM %i WHERE %i = {$placeholder} LIMIT 1";
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$query = $this->wpdb->prepare( $sql, $table, $column, $value );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $this->wpdb->get_var( $query ) !== null;
	}

	/**
	 * Inserts a new row into the given table.
	 *
	 * @since 1.0.0
	 *
	 * @param string $table The table name.
	 * @param array<string, scalar|null> $data The column-value pairs to insert.
	 *
	 * @return bool True if the insert was successful, false otherwise.
	 */
	public function insert( string $table, array $data ): bool {

		return TypeCaster::to_bool(
			$this->wpdb->insert(
				$table,
				$data,
			),
		);
	}

	/**
	 * Updates the row with the given ID using new values.
	 *
	 * @since 1.0.0
	 *
	 * @param string $table The table name.
	 * @param array<string, scalar|null> $data The column-value pairs to update.
	 * @param int|string $id The ID of the row to update.
	 *
	 * @return bool True if the update was successful, false otherwise.
	 */
	public function update( string $table, array $data, int|string $id ): bool {

		$result = $this->wpdb->update(
			$table,
			$data,
			[ 'id' => $id ],
		);

		return $result !== false;
	}

	/**
	 * Deletes the row with the given ID.
	 *
	 * @since 1.0.0
	 *
	 * @param string $table The table name.
	 * @param int|string $id The ID of the row to delete.
	 *
	 * @return bool True if the delete was successful, false otherwise.
	 */
	public function delete( string $table, int|string $id ): bool {

		return TypeCaster::to_bool(
			$this->wpdb->delete(
				$table,
				[ 'id' => $id ],
			),
		);
	}

	/**
	 * Executes a raw SQL query.
	 *
	 * @since 1.0.0
	 *
	 * @param string $sql The SQL query to execute.
	 *
	 * @return bool True if the query was executed successfully, false otherwise.
	 */
	public function query( string $sql ): bool {

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$result = $this->wpdb->query( $sql );

		return $result !== false;
	}

	/**
	 * Returns the charset and collation string for the database.
	 *
	 * @since 1.0.0
	 *
	 * @return string The charset and collation string, e.g. "DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci".
	 */
	public function get_charset_collate(): string {

		return $this->wpdb->get_charset_collate();
	}

	/**
	 * Sanitizes raw database values.
	 *
	 * Ensures consistent data shape and removes untyped structures.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $raw_row The raw row from the database.
	 *
	 * @return array<string, scalar|null> The sanitized row.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function sanitize_db_row( array $raw_row ): array {

		$sanitized = [];

		foreach ( $raw_row as $key => $value ) {

			$sanitized[ $key ] = $value === null ? null : TypeCaster::to_scalar( $value );
		}

		return $sanitized;
	}
}
