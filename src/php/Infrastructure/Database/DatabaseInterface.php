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
	 * Fetches the row with the given ID.
	 *
	 * @since 1.0.0
	 *
	 * @param string $table The table name.
	 * @param int|string $id The ID of the row to fetch.
	 *
	 * @return array<string, scalar|null>|null The result row, or null if not found.
	 */
	public function get_by_id( string $table, int|string $id ): ?array;

	/**
	 * Retrieves all rows from the given table.
	 *
	 * @since 1.0.0
	 *
	 * @param string $table The table name.
	 *
	 * @return array<array<string,scalar|null>> The list of rows.
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
	 * @return bool True if a matching row exists, false otherwise.
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
	 * @return bool True if a matching row exists, false otherwise.
	 */
	public function exists_by_column( string $table, string $column, int|float|string|bool|null $value ): bool;

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
	public function insert( string $table, array $data ): bool;

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
	public function update( string $table, array $data, int|string $id ): bool;

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
	public function delete( string $table, int|string $id ): bool;

	/**
	 * Executes a raw SQL query.
	 *
	 * @since 1.0.0
	 *
	 * @param string $sql The SQL query to execute.
	 *
	 * @return bool True if the query was executed successfully, false otherwise.
	 */
	public function query( string $sql ): bool;

	/**
	 * Returns the charset and collation string for the database.
	 *
	 * @since 1.0.0
	 *
	 * @return string The charset and collation string, e.g. "DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci".
	 */
	public function get_charset_collate(): string;
}
