<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\Migrations;

use Fundrik\WordPress\Infrastructure\Migrations\AbstractMigration;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationException;
use Fundrik\WordPress\Tests\Fixtures\Migrations\EmptyVersionMigration;
use Fundrik\WordPress\Tests\Fixtures\Migrations\InvalidVersionPrefixMigration;
use Fundrik\WordPress\Tests\Fixtures\Migrations\InvalidVersionSuffixMigration;
use Fundrik\WordPress\Tests\Fixtures\Migrations\OldMigration;
use Fundrik\WordPress\Tests\Fixtures\Migrations\WhitespacedVersionMigration;
use Fundrik\WordPress\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( AbstractMigration::class )]
final class AbstractMigrationTest extends FundrikTestCase {

	#[Test]
	public function it_returns_the_defined_version(): void {

		$this->assertSame( '2000_01_16_00', OldMigration::version() );
	}

	#[Test]
	public function it_throws_if_version_is_empty(): void {

		$this->expectException( MigrationException::class );
		$this->expectExceptionMessage( 'Migration version must follow "YYYY_MM_DD_XX". Given: .' );

		EmptyVersionMigration::version();
	}

	#[Test]
	public function it_throws_if_version_is_only_whitespace(): void {

		$this->expectException( MigrationException::class );
		$this->expectExceptionMessage( 'Migration version must follow "YYYY_MM_DD_XX". Given:  .' );

		WhitespacedVersionMigration::version();
	}

	#[Test]
	public function it_throws_if_version_has_invalid_format_due_to_prefix(): void {

		$this->expectException( MigrationException::class );
		$this->expectExceptionMessage( 'Migration version must follow "YYYY_MM_DD_XX". Given: v2025_01_01_00.' );

		InvalidVersionPrefixMigration::version();
	}

	#[Test]
	public function it_throws_if_version_has_invalid_format_due_to_suffix(): void {

		$this->expectException( MigrationException::class );
		$this->expectExceptionMessage( 'Migration version must follow "YYYY_MM_DD_XX". Given: 2025_01_01_00x.' );

		InvalidVersionSuffixMigration::version();
	}
}
