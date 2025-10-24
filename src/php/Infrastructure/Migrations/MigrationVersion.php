<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Migrations;

use Attribute;

/**
 * Declares the version identifier for a migration class.
 *
 * Used to determine the execution order and track applied migrations.
 * The version must follow a sortable format, such as `YYYY_MM_DD_XX`.
 *
 * @since 1.0.0
 *
 * @internal
 */
#[Attribute( Attribute::TARGET_CLASS )]
final readonly class MigrationVersion {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value The migration version.
	 */
	public function __construct(
		public string $value,
	) {}
}
