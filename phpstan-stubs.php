<?php

/**
 * WordPress database access abstraction class.
 *
 * This class is used to interact with a database without needing to use raw SQL statements.
 * By default, WordPress uses this class to instantiate the global $wpdb object, providing
 * access to the WordPress database.
 *
 * It is possible to replace the global instance with your own by setting the $wpdb global variable
 * in wp-content/db.php file to your class. The wpdb class will still be included, so you can
 * extend it or simply use your own.
 *
 * @link https://developer.wordpress.org/reference/classes/wpdb/
 *
 * @since 0.71
 */
#[\AllowDynamicProperties]
class wpdb {

	/**
	 * The error encountered during the last query.
	 *
	 * @since 2.5.0
	 *
	 * @var string
	 */
	public $last_error = '';

	/**
	 * Prepares a SQL query for safe execution.
	 *
	 * Uses `sprintf()`-like syntax. The following placeholders can be used in the query string:
	 *
	 * - `%d` (integer)
	 * - `%f` (float)
	 * - `%s` (string)
	 * - `%i` (identifier, e.g. table/field names)
	 *
	 * All placeholders MUST be left unquoted in the query string. A corresponding argument
	 * MUST be passed for each placeholder.
	 *
	 * Note: There is one exception to the above: for compatibility with old behavior,
	 * numbered or formatted string placeholders (eg, `%1$s`, `%5s`) will not have quotes
	 * added by this function, so should be passed with appropriate quotes around them.
	 *
	 * Literal percentage signs (`%`) in the query string must be written as `%%`. Percentage wildcards
	 * (for example, to use in LIKE syntax) must be passed via a substitution argument containing
	 * the complete LIKE string, these cannot be inserted directly in the query string.
	 * Also see wpdb::esc_like().
	 *
	 * Arguments may be passed as individual arguments to the method, or as a single array
	 * containing all arguments. A combination of the two is not supported.
	 *
	 * Examples:
	 *
	 *     $wpdb->prepare(
	 *         "SELECT * FROM `table` WHERE `column` = %s AND `field` = %d OR `other_field` LIKE %s",
	 *         array( 'foo', 1337, '%bar' )
	 *     );
	 *
	 *     $wpdb->prepare(
	 *         "SELECT DATE_FORMAT(`field`, '%%c') FROM `table` WHERE `column` = %s",
	 *         'foo'
	 *     );
	 *
	 *     $wpdb->prepare(
	 *         "SELECT * FROM %i WHERE %i = %s",
	 *         $table,
	 *         $field,
	 *         $value
	 *     );
	 *
	 * @since 2.3.0
	 * @since 5.3.0 Formalized the existing and already documented `...$args` parameter
	 *              by updating the function signature. The second parameter was changed
	 *              from `$args` to `...$args`.
	 * @since 6.2.0 Added `%i` for identifiers, e.g. table or field names.
	 *              Check support via `wpdb::has_cap( 'identifier_placeholders' )`.
	 *              This preserves compatibility with `sprintf()`, as the C version uses
	 *              `%d` and `$i` as a signed integer, whereas PHP only supports `%d`.
	 *
	 * @link https://www.php.net/sprintf Description of syntax.
	 *
	 * @param string      $query   Query statement with `sprintf()`-like placeholders.
	 * @param array|mixed $args    The array of variables to substitute into the query's placeholders
	 *                             if being called with an array of arguments, or the first variable
	 *                             to substitute into the query's placeholders if being called with
	 *                             individual arguments.
	 * @param mixed       ...$args Further variables to substitute into the query's placeholders
	 *                             if being called with individual arguments.
	 * @return string|null Sanitized query string, if there is a query to prepare.
	 */
	public function prepare($query, ...$args) {}

	/**
	 * Retrieves one value from the database.
	 *
	 * Executes a SQL query and returns the value from the SQL result.
	 * If the SQL result contains more than one column and/or more than one row,
	 * the value in the column and row specified is returned. If $query is null,
	 * the value in the specified column and row from the previous SQL result is returned.
	 *
	 * Returns null both on failure and when the matched cell value is an empty
	 * string. To distinguish the two cases, check {@see self::$last_error}.
	 *
	 * @since 0.71
	 *
	 * @param string|null $query Optional. SQL query. Defaults to null, use the result from the previous query.
	 * @param int         $x     Optional. Column of value to return. Indexed from 0. Default 0.
	 * @param int         $y     Optional. Row of value to return. Indexed from 0. Default 0.
	 * @return string|null Database query result (as string), or null on failure or when the value is an empty string.
	 * @phpstan-return non-empty-string|null
	 * 
	 * @phpstan-impure
	 */
	public function get_var($query = \null, $x = 0, $y = 0) {}

	/**
	 * Retrieves one row from the database.
	 *
	 * Executes a SQL query and returns the row from the SQL result.
	 *
	 * @since 0.71
	 *
	 * @param string|null $query  SQL query.
	 * @param string      $output Optional. The required return type. One of OBJECT, ARRAY_A, or ARRAY_N, which
	 *                            correspond to an stdClass object, an associative array, or a numeric array,
	 *                            respectively. Default OBJECT.
	 * @param int         $y      Optional. Row to return. Indexed from 0. Default 0.
	 * @return array|object|null Database query result in format specified by $output or null on failure.
	 * @phpstan-param 'OBJECT'|'ARRAY_A'|'ARRAY_N' $output
	 * @phpstan-return (
	 *     $query is non-falsy-string
	 *         ? (
	 *             $output is 'OBJECT'
	 *                 ? stdClass|null
	 *                 : (
	 *                     $output is 'ARRAY_A'
	 *                         ? array<array-key, mixed>|null
	 *                         : (
	 *                             $output is 'ARRAY_N'
	 *                                 ? list<mixed>|null
	 *                                 : null
	 *                         )
	 *                 )
	 *         )
	 *         : null
	 * )
	 * @phpstan-param int<0, max> $y
	 * 
	 * @phpstan-impure
	 */
	public function get_row($query = \null, $output = \OBJECT, $y = 0) {}

	/**
	 * Retrieves an entire SQL result set from the database (i.e., many rows).
	 *
	 * Executes a SQL query and returns the entire SQL result.
	 *
	 * Returns an empty array when no rows match or when the database
	 * reports an error for the query. Returns null when $query is empty,
	 * when $output is not one of the recognized constants, or when the
	 * query cannot run because the connection is not ready.
	 *
	 * @since 0.71
	 *
	 * @param string|null $query  SQL query.
	 * @param string      $output Optional. Any of ARRAY_A | ARRAY_N | OBJECT | OBJECT_K constants.
	 *                            With one of the first three, return an array of rows indexed
	 *                            from 0 by SQL result row number. Each row is an associative array
	 *                            (column => value, ...), a numerically indexed array (0 => value, ...),
	 *                            or an object ( ->column = value ), respectively. With OBJECT_K,
	 *                            return an associative array of row objects keyed by the value
	 *                            of each row's first column's value. Duplicate keys are discarded.
	 *                            Default OBJECT.
	 * @return array|null Database query results. Empty array when no rows match
	 *                    or on database error. Null when $query is empty, when
	 *                    $output is invalid, or when the connection is not ready.
	 * @phpstan-param 'OBJECT'|'OBJECT_K'|'ARRAY_A'|'ARRAY_N' $output
	 * @phpstan-return (
	 *     $query is non-falsy-string
	 *         ? (
	 *             $output is 'OBJECT'
	 *                 ? list<stdClass>|null
	 *                 : (
	 *                     $output is 'OBJECT_K'
	 *                         ? array<array-key, stdClass>
	 *                         : (
	 *                             $output is 'ARRAY_A'
	 *                                 ? list<array<array-key, mixed>>
	 *                                 : (
	 *                                     $output is 'ARRAY_N'
	 *                                         ? list<list<mixed>>
	 *                                         : null
	 *                                 )
	 *                         )
	 *                 )
	 *         )
	 *         : null
	 * )
	 * 
	 * @phpstan-impure
	 */
	public function get_results($query = \null, $output = \OBJECT) {}
}
