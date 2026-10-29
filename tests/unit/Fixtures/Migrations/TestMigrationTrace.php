<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures\Migrations;

/**
 * Records applied test migrations in order.
 */
final class TestMigrationTrace {

	/**
	 * Recorded migration calls.
	 *
	 * @var list<array{class-string<AbstractTestMigration>,string}>
	 */
	private static array $calls = [];

	public static function reset(): void {

		self::$calls = [];
	}

	/**
	 * Records an applied migration.
	 *
	 * @param class-string<AbstractTestMigration> $migration_class Migration class.
	 * @param string $charset_collate Charset and collation.
	 */
	public static function record( string $migration_class, string $charset_collate ): void {

		self::$calls[] = [ $migration_class, $charset_collate ];
	}

	/**
	 * Returns recorded migration calls.
	 *
	 * @return list<array{class-string<AbstractTestMigration>,string}> Recorded migration calls.
	 */
	public static function calls(): array {

		return self::$calls;
	}
}
