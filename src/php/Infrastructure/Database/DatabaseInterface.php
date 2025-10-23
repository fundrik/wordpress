<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Database;

/**
 * Provides methods for accessing database queries and schema metadata.
 *
 * @since 1.0.0
 *
 * @internal
 */
interface DatabaseInterface {

	/**
	 * Fetches the row by the given ID.
	 *
	 * @since 1.0.0
	 *
	 * @param string $table The table name.
	 * @param int|string $id The ID of the row to fetch.
	 *
	 * @return array<string, int|float|string|bool|null>|null The row data if found, null otherwise.
	 *
	 * @throws DatabaseException When the query fails.
	 */
	public function get_by_id( string $table, int|string $id ): ?array;

	/**
	 * Retrieves all rows from the given table.
	 *
	 * @since 1.0.0
	 *
	 * @param string $table The table name.
	 *
	 * @return array<array<string, int|float|string|bool|null>> The list of rows (empty if none).
	 *
	 * @throws DatabaseException When the query fails.
	 */
	public function get_all( string $table ): array;

	/**
	 * Determines whether the table contains a row with the given ID.
	 *
	 * @since 1.0.0
	 *
	 * @param string $table The table name.
	 * @param int|string $id The ID to look up.
	 *
	 * @return bool True if a matching row exists.
	 *
	 * @throws DatabaseException When the query fails.
	 */
	public function exists( string $table, int|string $id ): bool;

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
	public function exists_by_column( string $table, string $column, int|float|string|bool|null $value ): bool;

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
	public function insert( string $table, array $data ): void;

	/**
	 * Updates the row with the given ID using new values.
	 *
	 * @since 1.0.0
	 *
	 * @param string $table The table name.
	 * @param array<string, int|float|string|bool|null> $data The column-value pairs to update.
	 * @param int|string $id The ID of the row to update.
	 *
	 * @throws DatabaseException When the update fails.
	 */
	public function update( string $table, array $data, int|string $id ): void;

	/**
	 * Deletes the row with the given ID.
	 *
	 * @since 1.0.0
	 *
	 * @param string $table The table name.
	 * @param int|string $id The ID of the row to delete.
	 *
	 * @throws DatabaseException When the delete fails.
	 */
	public function delete( string $table, int|string $id ): void;

	/**
	 * Executes a raw SQL query.
	 *
	 * @since 1.0.0
	 *
	 * @param string $sql The SQL to execute.
	 *
	 * @throws DatabaseException When execution fails.
	 */
	public function query( string $sql ): void;

	/**
	 * Returns the charset and collation string for the database.
	 *
	 * @since 1.0.0
	 *
	 * @return string The charset and collation string, e.g. "DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci".
	 *
	 * @throws DatabaseException When the information cannot be determined.
	 */
	public function get_charset_collate(): string;
}
