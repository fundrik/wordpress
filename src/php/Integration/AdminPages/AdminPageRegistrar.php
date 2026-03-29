<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\AdminPages;

/**
 * Registers all configured admin pages in WordPress.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class AdminPageRegistrar {

	/**
	 * The configured admin pages.
	 *
	 * @var list<AdminPageInterface>
	 */
	private array $admin_pages;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param AdminPageInterface ...$admin_pages Admin pages to register.
	 */
	public function __construct( AdminPageInterface ...$admin_pages ) {

		$this->admin_pages = $admin_pages;
	}

	/**
	 * Registers all configured admin pages.
	 *
	 * @since 1.0.0
	 */
	public function register_all(): void {

		foreach ( $this->admin_pages as $admin_page ) {
			$admin_page->register();
		}
	}

	/**
	 * Returns the number of configured admin pages.
	 *
	 * @since 1.0.0
	 *
	 * @return int Admin page count.
	 */
	public function count(): int {

		return count( $this->admin_pages );
	}
}
