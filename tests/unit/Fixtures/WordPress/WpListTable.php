<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures\WordPress;

/**
 * Provides a minimal WordPress list table for tests.
 */
abstract class WpListTable {

	/**
	 * List table items.
	 *
	 * @var array<mixed>
	 */
	// phpcs:ignore SlevomatCodingStandard.Classes.ForbiddenPublicProperty.ForbiddenPublicProperty -- Matches the WordPress WP_List_Table API.
	public array $items = [];

	/**
	 * Column headers.
	 *
	 * @var array<mixed>
	 */
	// phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore -- Matches the WordPress WP_List_Table API.
	protected array $_column_headers = [];

	/**
	 * Constructor arguments.
	 *
	 * @var array<string, mixed>
	 */
	private array $test_args;

	/**
	 * Pagination arguments.
	 *
	 * @var array<string, int>
	 */
	private array $test_pagination_args = [];

	private int $test_page_number = 1;

	/**
	 * Creates the list table.
	 *
	 * @param array<string, mixed> $args List table arguments.
	 */
	public function __construct( array $args = [] ) {

		$this->test_args = $args;
	}

	/**
	 * Prepares items for display.
	 */
	abstract public function prepare_items(): void;

	/**
	 * Returns table columns.
	 *
	 * @return array<string, string> Table columns.
	 */
	abstract public function get_columns(): array;

	/**
	 * Returns default column output.
	 *
	 * @param mixed $item List table item.
	 * @param string $column_name Column name.
	 *
	 * @return string Column output.
	 */
	abstract public function column_default( $item, string $column_name ): string;

	/**
	 * Renders the no items message.
	 */
	abstract public function no_items(): void;

	/**
	 * Returns the current page number.
	 */
	public function get_pagenum(): int {

		return $this->test_page_number;
	}

	/**
	 * Stores pagination arguments.
	 *
	 * @param array<string, int> $args Pagination arguments.
	 */
	protected function set_pagination_args( array $args ): void {

		$this->test_pagination_args = $args;
	}

	/**
	 * Renders minimal table markup.
	 */
	public function display(): void {

		echo '<table></table>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed test markup.
	}

	/**
	 * Returns constructor arguments for assertions.
	 *
	 * @return array<string, mixed> Constructor arguments.
	 */
	public function get_test_args(): array {

		return $this->test_args;
	}

	/**
	 * Returns column headers for assertions.
	 *
	 * @return array<mixed> Column headers.
	 */
	public function get_test_column_headers(): array {

		return $this->_column_headers;
	}

	/**
	 * Returns pagination arguments for assertions.
	 *
	 * @return array<string, int> Pagination arguments.
	 */
	public function get_test_pagination_args(): array {

		return $this->test_pagination_args;
	}

	/**
	 * Sets the current page number for tests.
	 *
	 * @param int $page Page number.
	 */
	public function set_test_page_number( int $page ): void {

		$this->test_page_number = $page;
	}
}
