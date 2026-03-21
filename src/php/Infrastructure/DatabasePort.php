<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure;

/**
 * Provides the outbound port for database access.
 *
 * @since 1.0.0
 *
 * @internal
 */
interface DatabasePort {

	/**
	 * Fetches the row by the given ID.
	 *
	 * @since 1.0.0
	 *
	 * @param string $table The table name.
	 * @param int|string $id The row ID to fetch.
	 *
	 * @return array<string, int|float|string|bool|null>|null The row data if found, null otherwise.
	 *
	 * @throws DatabaseExceptionInterface When the query fails.
	 */
	public function get_by_id( string $table, int|string $id ): ?array;

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
	 * @throws DatabaseExceptionInterface When the query fails.
	 */
	public function get_all( string $table ): array;

	/**
	 * Retrieves all rows from the given table filtered by a column value.
	 *
	 * @since 1.0.0
	 *
	 * @param string $table The table name.
	 * @param string $column The column to filter by.
	 * @param int|float|string|bool|null $value The value to match.
	 *
	 * @return array<array<string, int|float|string|bool|null>> The list of matching rows.
	 *
	 * @phpstan-return list<array<string, int|float|string|bool|null>>
	 *
	 * @throws DatabaseExceptionInterface When the query fails.
	 */
	public function get_all_by_column( string $table, string $column, int|float|string|bool|null $value ): array;

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
	 * @throws DatabaseExceptionInterface When the query fails.
	 */
	public function exists_by_id( string $table, int|string $id ): bool;

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
	 * @throws DatabaseExceptionInterface When the query fails.
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
	 * @throws DatabaseDuplicateKeyExceptionInterface When the insert fails because of a duplicate key.
	 * @throws DatabaseExceptionInterface When the insert fails.
	 */
	public function insert( string $table, array $data ): void;

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
	 * @throws DatabaseExceptionInterface When the update fails.
	 */
	public function update( string $table, array $data, array $where ): int;

	/**
	 * Deletes the row with the given ID.
	 *
	 * @since 1.0.0
	 *
	 * @param string $table The table name.
	 * @param int|string $id The row ID to delete.
	 *
	 * @throws DatabaseExceptionInterface When the delete fails.
	 */
	public function delete( string $table, int|string $id ): void;

	/**
	 * Executes a raw SQL query.
	 *
	 * @since 1.0.0
	 *
	 * @param string $sql The SQL to execute.
	 *
	 * @throws DatabaseExceptionInterface When execution fails.
	 */
	public function query( string $sql ): void;

	/**
	 * Executes a SQL query with bound placeholder values.
	 *
	 * @since 1.0.0
	 *
	 * @param string $sql The SQL query template with placeholders.
	 * @param int|float|string|bool|null ...$args The values to bind to placeholders.
	 *
	 * @throws DatabaseExceptionInterface When preparing or executing fails.
	 */
	public function query_with_args( string $sql, int|float|string|bool|null ...$args ): void;

	/**
	 * Resolves table name using the configured table prefix.
	 *
	 * @since 1.0.0
	 *
	 * @param string $table The table name with or without prefix.
	 *
	 * @return string The table name with prefix applied.
	 *
	 * @throws DatabaseExceptionInterface When the prefix cannot be determined.
	 */
	public function qualify_table_name( string $table ): string;

	/**
	 * Returns the charset and collation string for the database.
	 *
	 * @since 1.0.0
	 *
	 * @return string The charset and collation string, e.g. "DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci".
	 *
	 * @throws DatabaseExceptionInterface When the information cannot be determined.
	 */
	public function get_charset_collate(): string;
}
