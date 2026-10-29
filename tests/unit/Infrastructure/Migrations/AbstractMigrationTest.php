<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\Migrations;

use Fundrik\WordPress\Infrastructure\Migrations\AbstractMigration;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationException;
use Fundrik\WordPress\Tests\Fixtures\Migrations\EmptyVersionMigration;
use Fundrik\WordPress\Tests\Fixtures\Migrations\InvalidVersionPrefixMigration;
use Fundrik\WordPress\Tests\Fixtures\Migrations\InvalidVersionSuffixMigration;
use Fundrik\WordPress\Tests\Fixtures\Migrations\WhitespaceVersionMigration;
use Fundrik\WordPress\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( AbstractMigration::class )]
final class AbstractMigrationTest extends FundrikTestCase {

	#[Test]
	#[DataProvider( 'invalid_version_cases' )]
	public function version_throws_for_invalid_migration_version( string $migration_class, string $given_version ): void {

		$this->expectException( MigrationException::class );
		$this->expectExceptionMessage(
			sprintf( 'Migration version must follow "YYYY_MM_DD_XX". Given: %s.', $given_version ),
		);

		$migration_class::version();
	}

	public static function invalid_version_cases(): iterable {

		yield 'empty version' => [ EmptyVersionMigration::class, '' ];
		yield 'whitespace version' => [ WhitespaceVersionMigration::class, ' ' ];
		yield 'invalid prefix' => [ InvalidVersionPrefixMigration::class, 'v2025_01_01_00' ];
		yield 'invalid suffix' => [ InvalidVersionSuffixMigration::class, '2025_01_01_00x' ];
	}
}
